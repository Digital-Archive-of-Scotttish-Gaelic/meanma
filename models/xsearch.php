<?php

namespace models;

class xsearch
{
    public function getResults($params) {
        $rows = [];
        $count = 0;
        $response = $this->_getCurlResponse($params);   //query eXist/Elemental and get the results
        $data = json_decode($response, true);

        //check for server errors
        if ($data['error']) {
            return json_encode(["error:" => $data['error']]);
        }


        // Decode, restructure, and return

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
/*
        //The following code for EB API with pre and post context
        if (is_array($data['result'])) {
            $count = count($data['result']);
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
                        $rows[$i]['post'] .= $word['#text'] . ' ';
                    } else {
                        $rows[$i]['pre'] .= $word['#text'] . '  ';  //assemble pre context
                    }
                }
            }
        }
*/

        return json_encode([
            'total' => $count,
            'rows' => $rows
        ]);
    }

    private function _getCurlResponse($params) {

        $baseUrl = 'http://localhost:8080/exist/restxq/wordx';          // !! Note the change to 'wordx' here for non-context search !!
        $mode = ($params['mode'] != 'head-form') ? 'word-form' : 'head-form';
        $texts = (isset($params['text'])) ? $params['text'] : '';

        $curlParams = http_build_query([
            $mode => $params['q'],
            'text' => $texts,
            'experimental' => 'true'
        ]);

        $url = $baseUrl . '?' . $curlParams;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Optional: handle authentication
        curl_setopt($ch, CURLOPT_USERPWD, EXIST_USER . ":" . EXIST_PASSWORD);


        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return json_encode(['error' => curl_error($ch)]);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            return json_encode(['error' => "HTTP error $status"]);
        }

        return $response;
    }
}