<?php

namespace models;

class xsearch
{
    public function getResults($params) {

        $response = $this->_getCurlResponse($params);   //query exist/elemental and get the results

        // Decode, restructure, and return
        $data = json_decode($response, true);
        $rows = [];
/*
        //This code for new wordx without context
        foreach ($data['result'] as $i => $result) {

            $word = $result['w'];

            preg_match('/_(\d+(?:-\d+)?)_/', $word['wid'], $matches);
            $textId = $matches[1];;

            $rows[$i]['tid'] = $textId;
            $rows[$i]['filename'] = $textId . ".xml";

            $rows[$i]['match'] = $rows[$i]['wordform'] = $word['#text'];
            $rows[$i]['pos'] = $word['pos'];
            $rows[$i]['lemma'] = $word['lemma'];
            $rows[$i]['id'] = $word['wid'];
        }
*/
        //The following code for EB API with pre and post context
        foreach ($data['result'] as $i => $result) {
            $match = false;
            foreach ($result['w'] as $word) {

                preg_match('/_(\d+(?:-\d+)?)_/', $word['wid'], $matches);
                $textId = $matches[1];;

                $rows[$i]['textid'] = $textId;

                if ($word['match'] === 'true') {        //this is the matched word
                    $match = true;
                    $rows[$i]['match'] = $rows[$i]['wordform'] = $word['#text'];
                    $rows[$i]['pos'] = $word['pos'];
                    $rows[$i]['lemma'] = $word['lemma'];
                    $rows[$i]['id'] = $word['wid'];
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

    private function _getCurlResponse($params) {

        $baseUrl = 'http://localhost:8080/exist/restxq/word';
        $mode = ($params['mode'] != 'head-form') ? 'word-form' : 'head-form';
        $curlParams = http_build_query([
            $mode => $params['q'],
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

        return $response;
    }
}