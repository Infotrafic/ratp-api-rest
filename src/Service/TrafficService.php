<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\IxxiApiClient;
use App\Client\RatpWebsiteClient;
use App\Utils\NameHelper;

class TrafficService
{
    /**
     * @var IxxiApiClient
     */
    private $ixxiApiClient;

    /**
     * @var RatpWebsiteClient
     */
    private $ratpWebsiteClient;

    /**
     * @param IxxiApiClient $ixxiApiClient
     * @param RatpWebsiteClient $ratpWebsiteClient
     */
    public function __construct(IxxiApiClient $ixxiApiClient, RatpWebsiteClient $ratpWebsiteClient)
    {
        $this->ixxiApiClient     = $ixxiApiClient;
        $this->ratpWebsiteClient = $ratpWebsiteClient;
    }

    /**
     * @return array
     */
    public function fetchData(): array
    {
        $ixxiData = $this->ixxiApiClient->getData();
        $ratpData = $this->ratpWebsiteClient->getData();

        $mergedData = $this->mergeDataSources($ratpData, $ixxiData);

        return $mergedData;
    }

    /**
     * @param array $ratpData
     * @param array $ixxiData
     *
     * @return array
     */
    private function mergeDataSources(array $ratpData, array $ixxiData)
    {
        // merge only RER C, D and E
        $allowedRers = [
            'c',
            'd',
            'e'
        ];

        foreach ($allowedRers as $allowedRer) {
            if (isset($ixxiData['rers'][$allowedRer])) {
                $rer = $ixxiData['rers'][$allowedRer];
                ksort($rer);

                $firstEvent = current($rer);

                $information = [
                    'line'    => strtoupper($allowedRer),
                    'slug'    => NameHelper::statusSlug($firstEvent['typeName']),
                    'title'   => $firstEvent['typeName'],
                    'message' => $firstEvent['message']
                ];
            } else {
                $information = [
                    'line'    => strtoupper($allowedRer),
                    'slug'    => 'normal',
                    'title'   => 'Trafic normal',
                    'message' => 'Trafic normal sur l\'ensemble de la ligne.'
                ];
            }
            $tmpRers[$allowedRer] = $information;
        }

        ksort($tmpRers);

        foreach ($tmpRers as $rer) {
            $ratpData['rers'][] = $rer;
        }

        return $ratpData;
    }
}
