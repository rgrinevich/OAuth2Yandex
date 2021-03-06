<?php

namespace Kanboard\Plugin\OAuth2Yandex\Auth;

use Kanboard\Core\Base;
use Kanboard\Core\Security\OAuthAuthenticationProviderInterface;
use Kanboard\Plugin\OAuth2Yandex\User\GenericOAuth2UserProvider;

/**
 * GenericOAuth2Provider
 *
 * @package  Kanboard\Auth
 * @author   Roman Grinevich
 */
class GenericOAuth2Provider extends Base implements OAuthAuthenticationProviderInterface
{
    /**
     * User properties
     *
     * @access private
     * @var GenericOAuth2UserProvider
     */
    private $userInfo = null;

    /**
     * OAuth2Yandex instance
     *
     * @access protected
     * @var \Kanboard\Core\Http\OAuth2Yandex
     */
    protected $service;

    /**
     * OAuth2Yandex code
     *
     * @access protected
     * @var string
     */
    protected $code = '';

    /**
     * Get authentication provider name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'OAuth2Yandex';
    }

    /**
     * Authenticate the user
     *
     * @access public
     * @return boolean
     */
    public function authenticate()
    {
        $profile = $this->getProfile();

        if (! empty($profile)) {
            $this->userInfo = new GenericOAuth2UserProvider($this->container, $profile);
            return true;
        }

        return false;
    }

    /**
     * Set Code
     *
     * @access public
     * @param  string  $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get user object
     *
     * @access public
     * @return GenericOAuth2UserProvider
     */
    public function getUser()
    {
        return $this->userInfo;
    }

    /**
     * Get configured OAuth2Yandex service
     *
     * @access public
     * @return \Kanboard\Core\Http\OAuth2Yandex
     */
    public function getService()
    {
        if (empty($this->service)) {
            $this->service = $this->oauth->createService(
                $this->getClientId(),
                $this->getClientSecret(),
                $this->helper->url->to('OAuthController', 'handler', array('plugin' => 'OAuth2Yandex'), '', true),
                $this->getOAuthAuthorizeUrl(),
                $this->getOAuthTokenUrl(),
                array()
            );
        }
        
        return $this->service;
    }

    /**
     * Get user profile
     *
     * @access public
     * @return array
     */
    public function getProfile()
    {
        $token = $this->getService()->getAccessToken($this->code);

        if (DEBUG) {
            $this->logger->debug(__METHOD__.': Got access token: '.(empty($token) ? 'No' : 'Yes'));
            $this->logger->debug(__METHOD__.': Fetch user profile from '.$this->getUserAPiUrl());
        }
        
        return $this->httpClient->getJson(
            $this->getUserAPiUrl(),
            array($this->getService()->getAuthorizationHeader())
        );
    }

    /**
     * Unlink user
     *
     * @access public
     * @param  integer $userId
     * @return bool
     */
    public function unlink($userId)
    {
        return $this->userModel->update(array(
            'id' => $userId,
            'oauth2yandex_user_id' => '',
        ));
    }

    /**
     * Get client id
     *
     * @access public
     * @return string
     */
    public function getClientId()
    {
        return $this->configModel->get('oauth2yandex_client_id');
    }

    /**
     * Get client secret
     *
     * @access public
     * @return string
     */
    public function getClientSecret()
    {
        return $this->configModel->get('oauth2yandex_client_secret');
    }

    /**
     * Get authorize url
     *
     * @access public
     * @return string
     */
    public function getOAuthAuthorizeUrl()
    {
        return 'https://oauth.yandex.ru/authorize';
    }

    /**
     * Get token url
     *
     * @access public
     * @return string
     */
    public function getOAuthTokenUrl()
    {
        return 'https://oauth.yandex.ru/token';
    }

    /**
     * Get User API url
     *
     * @access public
     * @return string
     */
    public function getUserAPiUrl()
    {
        return 'https://login.yandex.ru/info';
    }
}
