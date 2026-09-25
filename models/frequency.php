<?php

namespace models;

class frequency
{
    public function getResults($params = [])

    {

        $type = (($params['type'] ?? '') === 'word-form')
            ? 'word-form'
            : 'lemma';

        $curlData = [];

        if (!empty($params['pos'])) {

            $curlData['pos'] = $params['pos'];

        }

        if (isset($params['min']) && $params['min'] !== '') {

            $curlData['min'] = max(1, (int)$params['min']);

        }

        if (isset($params['max']) && $params['max'] !== '') {

            $curlData['max'] = max(1, (int)$params['max']);

        }

        $curlData['start'] = isset($params['start'])

            ? max(1, (int)$params['start'])

            : 1;

        $curlData['limit'] = isset($params['limit'])

            ? max(1, min(500, (int)$params['limit']))

            : 50;

        $curlData['order'] =

            (($params['order'] ?? '') === 'asc')

                ? 'asc'

                : 'desc';

        $url =

            'http://localhost:8080/exist/restxq/frequency/' . $type . '?' .

            http_build_query($curlData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt(

            $ch,

            CURLOPT_USERPWD,

            EXIST_USER . ':' . EXIST_PASSWORD

        );

        $response = curl_exec($ch);

        if (curl_errno($ch)) {

            $error = curl_error($ch);

            curl_close($ch);

            return [

                'error' => $error

            ];

        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($status !== 200) {

            return [

                'error' => "HTTP error {$status}"

            ];

        }

        $xml = simplexml_load_string($response);

        if ($xml === false) {

            return [

                'error' => 'Invalid XML returned by frequency API'

            ];

        }

        $rows = [];

        foreach ($xml->entry as $entry) {

            $term = $type === 'word-form'
                ? (string)$entry['word-form']
                : (string)$entry['lemma'];

            $rows[] = [
                'term'      => $term,
                'frequency' => (int)$entry['frequency']
            ];
        }

        return [

            'error'     => null,

            'type'      => $type,

            'total'     => (int)$xml['total'],

            'start'     => (int)$xml['start'],

            'limit'     => (int)$xml['limit'],

            'order'     => (string)$xml['order'],

            'min'       => (int)$xml['min'],

            'max'       => isset($xml['max'])

                ? (int)$xml['max']

                : null,

            'pos'       => (string)$xml['pos'],

            'generated' => (string)$xml['generated'],

            'rows'      => $rows

        ];

    }
}