<?php

namespace models;

class xsearch
{
    public function getResults($params) {

        $baseUrl = 'http://localhost:8080/exist/restxq/word';
        $curlParams = http_build_query([
            'word-form' => $params['q'],
            'experimental' => 'true'
        ]);

        $url = $baseUrl . '?' . $curlParams;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Optional: handle authentication
        curl_setopt($ch, CURLOPT_USERPWD, "admin:");

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            return ['error' => "HTTP error $status"];
        }

        // Decode, restructure, and return
        $data = json_decode($response, true);
        $rows = [];

        foreach ($data['result'] as $i => $result) {
            foreach ($result['w'] as $word) {
                $rows[] = [
                    'text' => $word['#text'] ?? '',
                    'lemma' => $word['lemma'] ?? '',
                    'pos' => $word['pos'] ?? '',
                    'wid' => $word['wid'] ?? '',
                    'match' => ($word['match'] ?? '') === 'true' ? 'yes' : 'no',
                    'sentence' => $i + 1
                ];
            }
        }

        return json_encode([
            'total' => count($rows),
            'rows' => $rows
        ]);
    }
}