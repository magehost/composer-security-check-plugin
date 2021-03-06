<?php

namespace FancyGuy\Composer\SecurityCheck\Checker;

use Composer\CaBundle\CaBundle;
use FancyGuy\Composer\SecurityCheck\Exception\HttpException;
use FancyGuy\Composer\SecurityCheck\Exception\RuntimeException;
use FancyGuy\Composer\SecurityCheck\SecurityCheckPlugin;

class CurlChecker extends HttpChecker
{

    /**
     * {@inheritdoc}
     */
    protected function doHttpCheck($lock, $certFile)
    {
        if (false === $curl = curl_init()) {
            throw new RuntimeException('Unable to create a cURL handle.');
        }
        $tmplock = tempnam(sys_get_temp_dir(), 'composer_securitycheck');
        $handle = fopen($tmplock, 'w');
        fwrite($handle, $this->getLockContents($lock));
        fclose($handle);
        $postFields = array('lock' => PHP_VERSION_ID >= 50500 ? new \CurlFile($tmplock) : '@'.$tmplock);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_URL, $this->endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, ini_get('open_basedir') ? 0 : 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_USERAGENT, sprintf('Composer-SecurityCheckPlugin/%s CURL PHP', SecurityCheckPlugin::VERSION));
        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile) || (is_link($caPathOrFile) && is_dir(readlink($caPathOrFile)))) {
            curl_setopt($curl, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($curl, CURLOPT_CAINFO, $caPathOrFile);
        }
        $response = curl_exec($curl);
        unlink($tmplock);
        if (false === $response) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException(sprintf('An error occurred: %s.', $error));
        }
        $headersSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headersSize);
        $body = substr($response, $headersSize);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (400 == $statusCode) {
            $data = json_decode($body, true);
            $error = $data['error'];
            throw new RuntimeException($error);
        }
        if (200 != $statusCode) {
            throw new HttpException(sprintf('The web service failed for an unknown reason (HTTP %s).', $statusCode), $statusCode);
        }
        return array($headers, $body);
    }
}
