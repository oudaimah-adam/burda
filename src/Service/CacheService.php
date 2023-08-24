<?php

namespace Burda\Service;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class CacheService
{
    public function __construct(
        private TagAwareCacheInterface $cache,
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function cache(string                  $key,
                          ServiceEntityRepository $repository,
                          SerializerInterface     $serializer,
                          ?string                 $tagName = null,
                          bool                    $forceUpdate = false): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($serializer, $key, $repository, $tagName, $forceUpdate) {
            $data = $serializer->serialize($repository->find((int)$key), 'json', [
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['author']
            ]);
            if ($tagName) {
                $item->tag(str_replace('\\', '_', $tagName));
            }
            if ($forceUpdate) {
                $item->set($data);
            }
            $item->expiresAfter(60);
            return $data;
        });
    }
}