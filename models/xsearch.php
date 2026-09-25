<?php

namespace models;

class xsearch
{
    public function getResults($params, $func='word') {
        $rows = [];
        $count = 0;
        $total = null;

        $response = $this->_getCurlResponse($params, $func);   //query eXist/Elemental and get the results

        $data = json_decode($response, true);

        if (isset($data['total'])) {
            $total = (int)$data['total'];
        }

        // Dictionary view
        if ($func == 'xforms') {
            return $data;
        }

        //check for server errors
        if ($data['error']) {
            return json_encode(["error:" => $data['error']]);
        }

        // Decode, restructure, and return

        //This code for new wordx without context
        /*
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
        /*if (is_array($data['result'])) {

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

        if (isset($data['result']) && is_array($data['result'])) {

            $count = count($data['result']);

            foreach ($data['result'] as $i => $result) {

                $match = false;

                // A result can contain one or more line groups.
                $lineGroups = $result['lg'] ?? [];

                // Normalise a single <lg> into an array of <lg>s.
                if (isset($lineGroups['w'])) {
                    $lineGroups = [$lineGroups];
                }

                foreach ($lineGroups as $lineGroup) {

                    if (!isset($lineGroup['w'])) {
                        continue;
                    }

                    $words = $lineGroup['w'];

                    // Normalise a single <w> into an array of <w>s.
                    if (isset($words['#text'])) {
                        $words = [$words];
                    }

                    foreach ($words as $word) {

                        if (!isset($word['wid'])) {
                            continue;
                        }

                        if (preg_match('/_(\d+(?:-\d+)?)_/', $word['wid'], $matches)) {
                            $rows[$i]['textid'] = $matches[1];
                        }

                        if (($word['match'] ?? '') === 'true') {
                            // This is the matched word.
                            $match = true;

                            $rows[$i]['match'] =
                            $rows[$i]['wordform'] = $word['#text'] ?? '';

                            $rows[$i]['pos']   = $word['pos'] ?? '';
                            $rows[$i]['lemma'] = $word['lemma'] ?? '';
                            $rows[$i]['id']    = $word['wid'];

                            continue;
                        }

                        if ($match) {
                            $rows[$i]['post'] =
                                ($rows[$i]['post'] ?? '') .
                                ($word['#text'] ?? '') . ' ';
                        } else {
                            $rows[$i]['pre'] =
                                ($rows[$i]['pre'] ?? '') .
                                ($word['#text'] ?? '') . ' ';
                        }
                    }
                }
            }
        }


        return json_encode([
            'total' => $total ?? $count,
            'count' => $count,
            'rows' => $rows
        ]);
    }

    private function _getCurlResponse($params, $func) {

        $baseUrl = 'http://localhost:8080/exist/restxq/' . $func;          // !! Note the change to 'wordx' here for non-context search !!
        $mode = ($params['mode'] != 'head-form') ? 'word-form' : 'head-form';
        $texts = (isset($params['text'])) ? $params['text'] : '';

        $start = isset($params['start'])
            ? max(1, (int)$params['start'])
            : 1;

        $limit = isset($params['limit'])
            ? max(1, min(100, (int)$params['limit']))
            : 10;

        $includeTotal =
            !empty($params['include-total']) &&
            $params['include-total'] === 'true';

        $curlData = [
            $mode => $params['q'],
            'text' => $texts,
            'start' => $start,
            'limit' => $limit,
            'include-total' => $includeTotal ? 'true' : 'false'
        ];

        // Dictionary-view citation requests need to retain both the
        // head-form and POS restriction in addition to the surface form.
        if (!empty($params['head-form'])) {
            $curlData['head-form'] = $params['head-form'];
        }

        if (isset($params['pos']) && $params['pos'] !== '') {
            $curlData['pos'] = $params['pos'];
        }

        $curlParams = http_build_query($curlData);

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