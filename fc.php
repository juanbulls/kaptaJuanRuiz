#!/usr/bin/env php
<?php

/*
 * Simplest example to show how to interact with FC API.
 * WARN - public API is in BETA. Something may be changed (but not too globally).
 *
 * You need to replace 'YOUR REAL API KEY' and 'YOUR REAL API SECRET' with real keys and all should work.
 * 
 */

// always use false
define('F_DEV', false);

if (F_DEV) {
    $api_key    = '';
    $api_secret = '';
//    ini_set('error_log', '');
} else {
    $api_key    = 'YOUR REAL API KEY';
    $api_secret = 'YOUR REAL API SECRET';
}

$api = new Freedcamp_API($api_key, $api_secret);
fc_example($api);

class Freedcamp_API {

    const API_HOST     = 'https://freedcamp.com';
    const API_HOST_DEV = 'http://freedcamp.bear.com';
    const METHOD_GET   = 'GET';
    const METHOD_POST  = 'POST';

    private $api_key;
    private $api_secret;

    function __construct($api_key, $api_secret) {
        if (!$api_key || !$api_secret) {
            exit('Wrong parameters');
        }

        $this->api_key    = $api_key;
        $this->api_secret = $api_secret;
    }

    function request($path, $method = self::METHOD_GET, $payload = []) {
        $send_data = [];

        $send_data['api_key']   = $this->api_key;
        $send_data['timestamp'] = time();
        $send_data['hash']      = hash_hmac('sha1', $this->api_key . $send_data['timestamp'], $this->api_secret);

        $host = F_DEV ? self::API_HOST_DEV : self::API_HOST;
        $url  = $host . $path;
        $ch   = curl_init();

        $f_post = $method === self::METHOD_POST;

        $curl_config = array(
            CURLOPT_POST           => $f_post,
            CURLOPT_RETURNTRANSFER => true,
        );

        if ($f_post) {
            $send_data['data']               = json_encode($payload);
            $curl_config[CURLOPT_POSTFIELDS] = $send_data;
        } else {
            if ($payload) {
                $send_data = array_merge($send_data, $payload);
            }

            $query = http_build_query($send_data);
            if ($query) {
                $url = $url . '?' . $query;
            }
        }

        $curl_config[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $curl_config);

        if (F_DEV) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        $result = curl_exec($ch);

        $out_payload = null;
        $returned_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($returned_status >= 500) {
            e("Some internal error on server");
            return false;
        } else {
            $out_payload = json_decode($result, true);
            if ($out_payload === null) {
                e('failed to decode string: ' . $result);
                return false;
            }
        }

        if ($returned_status != 200 && $out_payload) {
            e($returned_status . ': ' . $out_payload['msg']);
        }

        curl_close($ch);
        return $out_payload;
    }

    function getSession() {
        $url = '/api/v1/sessions/current';
        return $this->request($url);
    }

    function getMyId() {
        $session_data = $this->getSession();
        if (!$session_data) {
            exit('Something is wrong');
        }

        return $session_data['data']['user_id'];
    }

    function getTasks($assigned_to, $limit = 5, $offset = 0) {
        $url = '/api/v1/tasks';

        $params                   = [];
        $params['limit']          = $limit;
        $params['offset']         = $offset;
        $params['assigned_to_id'] = [$assigned_to];
        $params['sort']           = ['due_date' => 'desc'];

        $response = $this->request($url, self::METHOD_GET, $params);
        if (!empty($response['data']['tasks'])) {
            return $response['data']['tasks'];
        } else {
            return [];
        }
    }

    function getNotifications($project_id = null) {
        $url = '/api/v1/notifications';

        if ($project_id) {
            $url .= '/' . $project_id;
        }

        $params                   = [];
        $params['following']      = 1;

        $response = $this->request($url, self::METHOD_GET, $params);

        if (!empty($response['data']['notifications'])) {
            e('My notifications are: ');
            e(implode(PHP_EOL, array_column($response['data']['notifications'], 'item_title')));
            return $response['data']['notifications'];
        } else {
            e('No notifications found');
            return [];
        }
    }

    function getProjects() {
        $session_data = $this->getSession();
        if (!$session_data) {
            exit('Something is wrong');
        }
        if (!empty($session_data['data']['projects'])) {
            return $session_data['data']['projects'];
        } else {
            return [];
        }
    }

    function getGroups() {
        $session_data = $this->getSession();
        if (!$session_data) {
            exit('Something is wrong');
        }
        if (!empty($session_data['data']['groups'])) {
            return $session_data['data']['groups'];
        } else {
            return [];
        }
    }

    function postTask() {
        $url = '/api/v1/tasks';

        $data = array(
            'task_group_id'        => 888, // real task group id
            'title'                => 'ssdcscsdc',
            'extended_description' => '',
            'assigned_to_id'       => 0,
            'priority'             => 1,
            'project_id'           => 616, // real project id
        );

        $result = $this->request($url, self::METHOD_POST, $data);
        return $result;
    }
}

function fc_example($api) {
    e('Simplest example for Freedcamp API');

    $my_user_id = $api->getMyId();
    e('My user id is: ' . $my_user_id);

    $groups = $api->getGroups();
    if ($groups) {
        e('My groups are: ');
        e(implode(PHP_EOL, array_column($groups, 'name')));
    } else {
        e('Groups not found');
    }

    $projects = $api->getProjects();
    if ($projects) {
        e('My projects are: ');
        e(implode(PHP_EOL, array_column($projects, 'project_name')));
    } else {
        e('Projects not found');
    }

    $tasks = $api->getTasks($my_user_id);
    if ($tasks) {
        e('My next tasks are: ');
        e(implode(PHP_EOL, array_column($tasks, 'title')));
    } else {
        e('My tasks not found');
    }

//    $api->postTask(); // example how to post a task
//    $api->getNotifications(); // example how to get notifications

    e('Example done.');
}

// making output prettier and faster
function e($arr) {
    if (is_array($arr) || is_object($arr)) {
        $t = print_r($arr, true);
    } else {
        $t = $arr;
    }
    $t = PHP_EOL . $t . PHP_EOL;
    echo($t);
}

function r($arr) {
    if (is_array($arr) || is_object($arr)) {
        $t = print_r($arr, true);
    } else {
        $t = $arr;
    }
    error_log($t);
}
