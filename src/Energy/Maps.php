<?php

namespace Energy;

class Maps
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function search($query)
    {
        $url = 'http://dev.virtualearth.net/REST/v1/Locations?q='.urlencode($query).'&key=' . $this->apiKey;

        $contents = file_get_contents($url);
        $result = json_decode($contents);

        $return = [];
        foreach($result->resourceSets[0]->resources as $resource) {
            $object = [
                'name' => $resource->name,
                'lat' => $resource->point->coordinates[0],
                'lon' => $resource->point->coordinates[1]
            ];
            $return[] = $object;
        }

        return $return;
    }
}
