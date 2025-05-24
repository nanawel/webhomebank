<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 01/07/15
 * Time: 21:17
 */

namespace app\models\core;

class Url extends \Prefab
{
    protected $_baseUrl = [];

    /**
     * Return the base URL when generating
     *
     * @param array $params
     * @return null|string
     */
    public function getBaseUrl(array $params = []) {
        asort($params);
        $paramsKey = crc32(serialize($params));
        if (!isset($this->_baseUrl[$paramsKey])) {
            $fw = \Base::instance();
            if (Main::app()->getConfig('USE_X_FORWARDED_HOST') && $host = $fw->get('HEADERS.X-Forwarded-Host')) {
                if (strstr($host, ':')) {
                    [$host, $port] = explode(':', $host);
                }

                if (!isset($port) || !$port) {
                    $port = $fw->get('SCHEME') == 'https' ? 443 : 80;
                }
            }
            else {
                $host = $fw->get('HOST');
                $port = $fw->get('PORT');
            }

            switch ($port) {
                case 80:
                    $port = $fw->get('SCHEME') == 'http' ? '' : ':' . $port;
                    break;

                case 443:
                    $port = $fw->get('SCHEME') == 'https' ? '' : ':' . $port;
                    break;

                default:
                    $port = ':' . $port;
            }

            $url = '//' . $host . $port . $this->_getBasePath() . '/';

            $addScheme = Main::app()->getConfig('URL_DEFAULT_FORCE_SCHEME');
            if (isset($params['_skip_scheme']) && $params['_skip_scheme']) {
                $addScheme = false;
            }

            if (isset($params['_force_scheme']) && $params['_force_scheme']) {
                $addScheme = true;
            }

            if ($addScheme) {
                return $fw->get('SCHEME') . ':' . $url;
            }

            $this->_baseUrl[$paramsKey] = $url;
        }

        return $this->_baseUrl[$paramsKey];
    }

    protected function _getBasePath(): string {
        $externalBasePath = trim(\Base::instance()->get('HEADERS.X-External-Base-Path'), '/');
        $localBase = trim(\Base::instance()->get('BASE'), '/');

        $basePath = [];
        if ($externalBasePath !== '' && $externalBasePath !== '0') {
            $basePath[] = $externalBasePath;
        }

        if ($localBase !== '' && $localBase !== '0') {
            $basePath[] = $localBase;
        }

        $basePath = implode('/', $basePath);
        if ($basePath !== '' && $basePath !== '0') {
            $basePath = '/' . $basePath;
        }

        return $basePath;
    }

    public function getUrl($path, $params = []): string {
        if ($params === null) {
            $params = [];
        }

        // Delegate path autocomplete to current controller
        $path = Main::app()->getCurrentController()->autocompleteUrlPath($path);

        $paramString = '';
        $sysParams = [];
        foreach($params as $k => $v) {
            if (strpos($k, '_') === 0) {
                $sysParams[$k] = $v;
            }
            else {
                $paramString .= urlencode($k) . '/' . urlencode($v);
            }
        }

        $query = '';
        if (isset($sysParams['_query'])) {
            if ($sysParams['_query'] === '*') {
                $query = \Base::instance()->get('QUERY');
            }
            elseif (is_array($sysParams['_query'])) {
                $separator = $query['_query_arg_separator'] ?? ini_get('arg_separator.output');
                $encType = $query['_query_enc_type'] ?? PHP_QUERY_RFC1738;
                $query = http_build_query($sysParams['_query'], '', $separator, $encType);
            }
        }

        return $this->getBaseUrl($sysParams) . trim($path . '/' . $paramString, '/ ') . ($query ? '?' . $query : '');
    }
} 