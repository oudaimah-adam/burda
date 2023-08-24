<?php

namespace Burda\Controller;

use Burda\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseController extends AbstractController
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly CacheService           $cache,
        protected readonly SerializerInterface    $serializer,
        protected readonly ValidatorInterface     $validator,
    )
    {
    }

    protected function response(bool $success = true, ?string $message = null, int $statusCode = 200, mixed $data = null): JsonResponse
    {
        return $this->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}