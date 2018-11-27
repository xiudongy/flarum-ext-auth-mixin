<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xiudongy\Auth\Mixin;

use Exception;
use Flarum\Forum\Auth\Registration;
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zamseam\Mixin\MixinClient;
use Flarum\User\User;


class MixinAuthController implements RequestHandlerInterface
{
    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param ResponseFactory $response
     */
    public function __construct(ResponseFactory $response, SettingsRepositoryInterface $settings)
    {
        $this->response = $response;
        $this->settings = $settings;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(Request $request): ResponseInterface
    {
        $client_id = $this->settings->get("flarum-auth-mixin.client_id");
        $client_secret = $this->settings->get("flarum-auth-mixin.client_secret");
        $private_key = $this->settings->get("flarum-auth-mixin.private_key");
        $session_id = $this->settings->get("flarum-auth-mixin.session_id");
        if (!isset($_GET['code'])) {
            $authorizationUrl = 'https://mixin.one/oauth/authorize?client_id='.$client_id.'&scope=PROFILE:READ+PHONE:READ+ASSETS:READ';
            header('Location: ' . $authorizationUrl);
            exit;
        } else {
            $mixin = new MixinClient([
                'uid' => '',
                'session_id' => $session_id,
                'private_key' => $private_key
            ]);
            /*
            if(isset($_COOKIE['mixin_access_token'])) {
                $token = $_COOKIE['mixin_access_token']; 
            } else {
                $mixin->setModel('oauth');
                $result = $mixin->getOauthToken($client_id, $_GET['code'], $client_secret);
                if(isset($result['data']['access_token'])) {
                    $token = $result['data']['access_token'];
                    setcookie("mixin_access_token", $token, time()+3600);
                } else {
                    echo '<pre>';var_dump($result);exit;
                }
            }
            */
            $mixin->setModel('oauth');
            $result = $mixin->getOauthToken($client_id, $_GET['code'], $client_secret);
            if(isset($result['data']['access_token'])) {
                $token = $result['data']['access_token'];
            }

            if($token) {
                $mixin->setModel('me');
                $profile = $mixin->readProfile($token);
                /*
                $identification = ['email' => $profile['data']['identity_number'].'@vcdiandian.com'];
                $suggestions = [
                    'username' => $profile['data']['full_name'],
                    'avatarUrl' => $profile['data']['avatar_url']
                ];
                */
                $email = $profile['data']['identity_number'].'@vcdiandian.com';
                $user = User::where('email', $email)->first();
                if(!$user) {
                    $user = new User;
                    $user->username = $profile['data']['full_name'];
                    $user->joined_at = time();
                    if($profile['data']['avatar_url']) 
                        $user->avatar_url = $profile['data']['avatar_url']; 
                    $user->email = $email; 
                    $user->password = md5($profile['data']['identity_number'].rand(100,1000000000)); 
                    $user->mixin_info = json_encode($profile['data']);
                    $user->is_email_confirmed = 1;
                    $user->save();
                }
                $response = $this->response->make(
                    'Mixin', $user->id,
                    function (Registration $registration) use ($user, $profile) {
                        $registration
                            ->provideTrustedEmail($user->email)
                            ->provideAvatar($profile['data']['avatar_url'], 'avatar_url')
                            ->suggestUsername($user->username)
                            ->setPayload($user->toArray());
                    }
                );
                //$response = $this->authResponse->make($request, $identification, $suggestions);
                /*
                if($this->isMobile()) {
                    header("Location:/");
                    exit;
                }
                */
                return $response;
            } 
        }
    }

    protected function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return TRUE;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])) {
            return stristr($_SERVER['HTTP_VIA'], "wap") ? TRUE : FALSE;// 找不到为flase,否则为TRUE
        }
        // 判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = [ 'mobile', 'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap' ];
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return TRUE;
            }
        }
        if (isset ($_SERVER['HTTP_ACCEPT'])) { // 协议法，因为有可能不准确，放到最后判断
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== FALSE) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === FALSE || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return TRUE;
            }
        }
        return FALSE;
    }
}
