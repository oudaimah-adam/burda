<?php

namespace Burda\Controller;

use Burda\Entity\Author;
use Burda\Entity\BlogPost;
use Burda\Repository\AuthorRepository;
use Burda\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthorController extends BaseController
{
    public function __construct(
        private readonly AuthorRepository $authorRepository,
        EntityManagerInterface            $entityManager,
        CacheService                      $cache,
        SerializerInterface               $serializer,
        ValidatorInterface                  $validator,
    )
    {
        parent::__construct($entityManager, $cache, $serializer, $validator);
    }

    #[Route(path: '/author/{id}', name: 'author_details', methods: 'GET')]
    public function index(int $id): JsonResponse
    {
        $data = $this->cache->cache($id, $this->authorRepository, $this->serializer, Author::class);
        if ($data === 'null') {
            return $this->response(
                false,
                sprintf('Author with id %s is not found', $id),
                404,
            );
        }
        return $this->response(
            message: 'Author retrieved successfully',
            data: $data,
        );
    }

    #[Route(path: '/author', name: 'author_create', methods: 'POST')]
    public function create(Request $request): JsonResponse
    {
        $parameters = $request->toArray();
        $author = new Author;
        $author->setName($parameters['name']);
        $author->setEmail($parameters['email'] ?? null);
        $author->setBiography($parameters['biography'] ?? null);
        $author->setBirthDate($parameters['birthdate'] ? new \DateTimeImmutable($parameters['birthdate']) : null);

        $errors = $this->validator->validate($author);
        if (count($errors) > 0) {
            $errorsString = (string)$errors;

            return $this->response(
                success: false,
                message: 'Error while creating a new Author',
                statusCode: 422,
                data: $errorsString
            );
        }

        $this->entityManager->persist($author);
        $this->entityManager->flush();

        if (isset($parameters['posts'])) {
            foreach ($request->get('posts') as $postItem) {
                $post = new BlogPost;
                $post->setTitle($postItem['title']);
                $post->setContent($postItem['content'] ?? null);
                $post->setPublicationDate(new \DateTimeImmutable($postItem['publicationDate'] ?? 'now'));
                $post->setAuthor($author);
                $this->entityManager->persist($post);
            }
            $this->entityManager->flush();
        }

        return $this->response(
            message: 'Author was created successfully',
            data: $this->serializer->serialize($author, 'json'),
        );
    }

    /**
     * @throws \JsonException
     */
    #[Route(path: '/author/{id}}', name: 'author_update', methods: 'PUT')]
    public function update(int $id, Request $request): JsonResponse
    {
        $parameters = $request->toArray();
        $author = $this->authorRepository->find($id);
        if (!$author) {
            return $this->response(
                false,
                sprintf('Author with id %s is not found', $id),
                404,
            );
        }
        $author->setName($parameters['name']);
        $author->setEmail($parameters['email'] ?? null);
        $author->setBiography($parameters['biography'] ?? null);
        $author->setBirthDate($parameters['birthdate'] ? new \DateTimeImmutable($parameters['birthdate']) : null);
        $this->entityManager->persist($author);
        $this->entityManager->flush();

        $data = $this->cache->cache($id, $this->authorRepository, $this->serializer, Author::class, true);

        return $this->response(
            message: 'Author was updated successfully',
            data: $data,
        );
    }

    #[Route(path: '/author/{id}', name: 'author_delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $author = $this->authorRepository->find($id);
        if (!$author) {
            return $this->response(
                false,
                sprintf('Author with id %s is not found', $id),
                404,
            );
        }

        $this->entityManager->remove($author);
        $this->entityManager->flush();

        return $this->response(
            message: sprintf('Author with id %s was deleted successfully', $id),
        );
    }
}
