<?php

declare(strict_types=1);

namespace App\Tests\Services;

use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Entity\Network as NetworkEntity;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class HttpResponseFactory
{
    /**
     * @param array<mixed> $data
     */
    public static function createJsonResponse(array $data): ResponseInterface
    {
        return new Response(
            200,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode($data)
        );
    }

    public static function fromDropletEntity(DropletEntity $dropletEntity): ResponseInterface
    {
        return self::createJsonResponse([
            'droplet' => self::createDropletData($dropletEntity),
        ]);
    }

    /**
     * @param DropletEntity[] $dropletEntities
     */
    public static function fromDropletEntityCollection(array $dropletEntities): ResponseInterface
    {
        $collectionData = [];

        foreach ($dropletEntities as $dropletEntity) {
            if ($dropletEntity instanceof DropletEntity) {
                $collectionData[] = self::createDropletData($dropletEntity);
            }
        }

        return self::createJsonResponse([
            'droplets' => $collectionData,
        ]);
    }

    /**
     * @return array<mixed>
     */
    private static function createDropletData(DropletEntity $dropletEntity): array
    {
        $dropletData = $dropletEntity->toArray();
        if (array_key_exists('networks', $dropletData)) {
            $dropletNetworksData = $dropletData['networks'];
            $networksData = [];

            foreach ($dropletNetworksData as $networkEntity) {
                if ($networkEntity instanceof NetworkEntity) {
                    $networkVersionKey = 'v' . (string) $networkEntity->version;
                    if (!array_key_exists($networkVersionKey, $networksData)) {
                        $networksData[$networkVersionKey] = [];
                    }

                    $networksData[$networkVersionKey][] = $networkEntity->toArray();
                }
            }

            $dropletData['networks'] = $networksData;
        }

        return $dropletData;
    }
}
