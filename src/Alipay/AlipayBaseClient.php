<?php
namespace OverNick\Payment\Alipay;

use OverNick\Payment\Kernel\ServiceContainer;
use OverNick\Payment\Kernel\Traits\HttpRequestTrait;
use OverNick\Support\Arr;

/**
 * Class AlipayBaseClient
 * @package OverNick\Payment\Alipay
 */
class AlipayBaseClient
{
    use HttpRequestTrait;

    /**
     * @var AliPayApp
     */
    protected $app;

    /**
     * @var string
     */
    protected $gateway = 'https://openapi.alipay.com/gateway.do';

    /**
     * @var string
     */
    protected $devGateway = 'https://openapi.alipaydev.com/gateway.do';

    /**
     * @var string
     */
    protected $format = 'JSON';

    /**
     * @var string
     */
    protected $chartSet = 'UTF-8';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * AlipayBaseClient constructor.
     * @param ServiceContainer $app
     */
    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }

    /**
     * @param array $params
     * @param string $method
     * @param array $options
     * @return mixed
     */
    protected function request(array $params, $method = 'POST',array $options = [])
    {
        $params = $this->buildPrams($params);

        $options = array_merge($options, [
            'http_errors' => false,
            'verify' => false,
            'form_params' => $params
        ]);

        $response = $this->getHttpClient()->request($method, $this->gateWay(), $options);

        $result = json_decode(trim($this->enCodeToUtf8($response->getBody()->getContents())), true);

        // 返回结果体，忽略sign和外层字段
        return Arr::get($result, str_replace('.','_',$params['method']).'_response');
    }

    /**
     * @param array $params
     * @return string
     */
    protected function buildUrl(array $params)
    {
        $params = $this->buildPrams($params);

        return $this->gateWay() . '?'. http_build_query($params);
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function buildPrams(array $attributes)
    {
        // 需要额外处理的字段
        $filed = [
            'method',
            'notify_url',
            'return_url',
            'app_auth_token'
        ];

        // 请求参数
        $params = Arr::only($attributes, $filed);
        $params['biz_content'] = $this->enCodeToUtf8(json_encode(Arr::except($attributes, $filed)));
        $params['app_id'] = $this->app->config->get('app_id');
        $params['sign_type'] = strtoupper($this->app->config->get('sign_type'));
        $params['format'] = $this->format;
        $params['charset'] = $this->chartSet;
        $params['version'] = $this->version;
        $params['timestamp'] = date("Y-m-d H:i:s");
        $params['sign'] = $this->app->getSign($params, $this->app->config->get('sign_type'));

        return $params;
    }

    /**
     * 字符转码
     *
     * @param $string
     * @param $from_encoding
     * @return string
     */
    public function enCodeToUtf8($string, $from_encoding = 'GBK')
    {
        return mb_convert_encoding($string, 'UTF-8', $from_encoding);
    }

    /**
     * @return string
     */
    protected function gateWay()
    {
        return $this->app->inSandBox() ? $this->devGateway : $this->gateway;
    }
}