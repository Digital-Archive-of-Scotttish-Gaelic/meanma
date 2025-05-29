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
            $match = false;
            foreach ($result['w'] as $word) {

                preg_match('/_(\d+(?:-\d+)?)_/', $word['wid'], $matches);
                $textId = $matches[1];;

                $rows[$i]['textid'] = $textId;

                if ($word['match'] === 'true') {
                    $match = true;
                    $rows[$i]['match'] = $word['#text'];
                    continue;
                }

                if ($match) {   // word has been matched so assemble post context
                    $rows[$i]['post'] .= $word['#text'] .  ' ';
                } else {
                    $rows[$i]['pre'] .= $word['#text'] . '  ';  //assemble pre context
                }
            }
        }

        return json_encode([
            'total' => count($data['result']),
            'rows' => $rows
        ]);
    }
}