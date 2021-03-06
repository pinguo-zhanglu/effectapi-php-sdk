<?php
namespace Camera360;

use Camera360\Http\Client;

/**
 * 实现身份认证的授权类
 *
 * @author zhanglu <zhanglu@camera360.com>
 *
 */
final class Authorization
{
    private $accessKey;
    private $secretKey;

    public function __construct($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    
    public function getAccessKey()
    {
        return $this->accessKey;
    }
    
    public function sign($data)
    {
        $hmac = hash_hmac('sha1', $data, $this->secretKey, true);
        return $this->accessKey . ':' . \Qiniu\base64_urlSafeEncode($hmac);
    }

    public function signRequest($urlString, $body, $contentType = null)
    {
        $url = parse_url($urlString);
        $data = '';
        if (array_key_exists('path', $url)) {
            $data = $url['path'];
        }
        if (array_key_exists('query', $url)) {
            $data .= '?' . $url['query'];
        }
        $data .= "\n";

        if ($body !== null && $contentType === 'application/x-www-form-urlencoded') {
            $data .= $body;
        }
        return $this->sign($data);
    }
    
    public function verifyCallback($contentType, $originAuthorization, $url, $body)
    {
        $authorization = 'Camera360 ' . $this->signRequest($url, $body, $contentType);
        return $originAuthorization === $authorization;
    }

    public function uploadToken($uploadOnly = true)
    {
        if ($uploadOnly) {
            $query['uploadOnly'] = 1;
        } else {
            $query['uploadOnly'] = 0;
        }
        $url = Conf::HOST . '/uploadtoken?' . http_build_query($query);
        
        $authHeaders = $this->doAuth($url);
        $response = Client::get($url, null, $authHeaders);
        if (!$response->ok()) {
            throw new \Exception($response->getMessage(), $response->getHttpcode());
        }
        $uploadToken = new UploadToken($response->getData());
        
        return $uploadToken;
    }

//     /**
//      *上传策略，参数规格详见
//      *http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
//      */
//     private static $policyFields = array(
//         'callbackUrl',
//         'callbackBody',
//         'callbackHost',
//         'callbackBodyType',
//         'callbackFetchKey',

//         'returnUrl',
//         'returnBody',

//         'endUser',
//         'saveKey',
//         'insertOnly',

//         'detectMime',
//         'mimeLimit',
//         'fsizeMin',
//         'fsizeLimit',

//         'persistentOps',
//         'persistentNotifyUrl',
//         'persistentPipeline',
        
//         'deleteAfterDays',

//         'upHosts',
//     );

//     private static $deprecatedPolicyFields = array(
//         'asyncOps',
//     );

//     private static function copyPolicy(&$policy, $originPolicy, $strictPolicy)
//     {
//         if ($originPolicy === null) {
//             return array();
//         }
//         foreach ($originPolicy as $key => $value) {
//             if (in_array((string) $key, self::$deprecatedPolicyFields, true)) {
//                 throw new \InvalidArgumentException("{$key} has deprecated");
//             }
//             if (!$strictPolicy || in_array((string) $key, self::$policyFields, true)) {
//                 $policy[$key] = $value;
//             }
//         }
//         return $policy;
//     }

    public function doAuth($url, $body = null, $contentType = null)
    {
        $authorization = 'Camera360 ' . $this->signRequest($url, $body, $contentType);
        return array('Authorization' => $authorization);
    }
}
