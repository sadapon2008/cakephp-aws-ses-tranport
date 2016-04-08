<?php

App::uses('AbstractTransport', 'Network/Email');

/**
 * Class AwsSesTransport
 */
class AwsSesTransport extends AbstractTransport
{
    /** @var Aws\Ses\SesClient */
    protected $ses = null;

    /** @var Aws\Result */
    protected $_lastResponse = null;

    /**
     * @param array|null $config
     * @return array
     */
    public function config($config = null) {
        if ($config === null) {
            return $this->_config;
        }
        $default = array(
            'region' => 'us-east-1',
            'version' => 'latest',
            'credential_key' => null,
            'credential_secret' => null,
        );
        $this->_config = array_merge($default, $this->_config, $config);
        return $this->_config;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function generateSes() {
        if($this->ses != null) {
            return;
        }
        $options = array(
            'region' => $this->_config['region'],
            'version' => $this->_config['version'],
        );
        if(($this->_config['credential_key'] != null) && ($this->_config['credential_secret'] != null)) {
            $options['credentials'] = array(
                'key' => $this->_config['credential_key'],
                'secret' => $this->_config['credential_secret'],
            );
        }
        $this->ses = new Aws\Ses\SesClient($options);
    }

    public function destroySes() {
        $this->ses = null;
    }

    public function getLastResponse() {
        return $this->_lastResponse;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getSendQuota() {
        $this->_lastResponse = array();
        $this->generateSes();
        $result = $this->ses->getSendQuota();
        $this->_lastResponse = $result;
    }

    /**
     * @param CakeEmail $email
     * @throws \InvalidArgumentException
     */
    public function send(CakeEmail $email) {
        $this->_lastResponse = array();
        $this->generateSes();
        $headers = $this->_headersToString($email->getHeaders(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'subject')));
        $message = implode("\r\n", (array)$email->message());

        $rawData = $headers . "\r\n\r\n" . $message;

        $options = array(
            'RawMessage' => array(
                'Data' => $rawData,
            ),
        );
        $result = $this->ses->sendRawEmail($options);

        $this->_lastResponse = $result;

        return array('headers' => $headers, 'message' => $message);
    }
}
