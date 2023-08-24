<?php

namespace Burda\Controller;

use Burda\Entity\BlogPost;
use Burda\Repository\AuthorRepository;
use Burda\Repository\BlogPostRepository;
use Burda\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BlogPostController extends BaseController
{
    public function __construct(
        private readonly AuthorRepository   $authorRepository,
        private readonly BlogPostRepository $blogPostRepository,
        EntityManagerInterface              $entityManager,
        CacheService                        $cache,
        SerializerInterface                 $serializer,
        ValidatorInterface                  $validator,
    )
    {
        parent::__construct($entityManager, $cache, $serializer, $validator);
    }

    #[Route(path: '/post/{id}', name: 'post_details', methods: 'GET')]
    public function index(int $id): JsonResponse
    {
        $data = $this->cache->cache($id, $this->blogPostRepository, $this->serializer, BlogPost::class);
        if ($data === 'null') {
            return $this->response(
                false,
                sprintf('BolgPost with id %s is not found', $id),
                404,
            );
        }
        return $this->response(
            message: 'BolgPost retrieved successfully',
            data: $data,
        );
    }

    #[Route(path: '/post', name: 'post_create', methods: 'POST')]
    public function create(Request $request): JsonResponse
    {
        $parameters = $request->toArray();
        $author = $this->authorRepository->find($parameters['author_id']);
        if (!$author) {
            return $this->response(
                false,
                sprintf('Author with id %s is not found', $parameters['author_id']),
                404,
            );
        }

        $post = new BlogPost;
        $post->setTitle($parameters['title']);
        $post->setContent($parameters['content'] ?? null);
        $post->setPublicationDate(new \DateTimeImmutable($parameters['publicationDate'] ?? 'now'));
        $post->setAuthor($author);

        $errors = $this->validator->validate($post);
        if (count($errors) > 0) {
            $errorsString = (string)$errors;

            return $this->response(
                success: false,
                message: 'Error while creating a new BolgPost',
                statusCode: 422,
                data: $errorsString
            );
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $this->response(
            message: 'BolgPost was created successfully',
            data: $this->serializer->serialize($post, 'json', [
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['author']
            ]),
        );
    }

    /**
     * @throws \JsonException
     */
    #[Route(path: '/post/{id}}', name: 'post_update', methods: 'POST')]
    public function update(int $id, Request $request): JsonResponse
    {
        $parameters = $request->toArray();
        $post = $this->blogPostRepository->find($id);
        if (!$post) {
            return $this->response(
                false,
                sprintf('BolgPost with id %s is not found', $id),
                404,
            );
        }
        $post->setTitle($parameters['title']);
        $post->setContent($parameters['content'] ?? null);
        $post->setPublicationDate(new \DateTimeImmutable($parameters['publicationDate'] ?? 'now'));
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $data = $this->cache->cache($id, $this->blogPostRepository, $this->serializer, BlogPost::class, true);

        return $this->response(
            message: 'BolgPost was updated successfully',
            data: $data,
        );
    }

    #[Route(path: '/post/{id}', name: 'post_delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $post = $this->blogPostRepository->find($id);
        if (!$post) {
            return $this->response(
                false,
                sprintf('BolgPost with id %s is not found', $id),
                404,
            );
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->response(
            message: sprintf('BolgPost with id %s was deleted successfully', $id),
        );
    }
}