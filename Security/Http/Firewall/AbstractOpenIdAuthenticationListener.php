<?php
namespace Fp\OpenIdBundle\Security\Http\Firewall;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

use Fp\OpenIdBundle\RelyingParty\RelyingPartyInterface;

abstract class AbstractOpenIdAuthenticationListener extends AbstractAuthenticationListener
{
    /**
     * @var \Fp\OpenIdBundle\RelyingParty\RelyingPartyInterface $relyingParty
     */
    private $relyingParty;

    /**
     * @var null|\Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, array $options = array(), AuthenticationSuccessHandlerInterface $successHandler = null, AuthenticationFailureHandlerInterface $failureHandler = null, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null)
    {
        $options = array_merge(array(
            'required_parameters' => array(),
            'optional_parameters' => array(),
        ), $options);

        parent::__construct($securityContext, $authenticationManager, $sessionStrategy, $httpUtils,$providerKey, $options, $successHandler, $failureHandler, $logger, $dispatcher);

        $this->dispatcher = $dispatcher;
    }

    /**
     * The relying party is required for the listener but since It is not possible overwrite constructor I use setter with the check in getter
     *
     * @param \Fp\OpenIdBundle\RelyingParty\RelyingPartyInterface $relyingParty
     */
    public function setRelyingParty(RelyingPartyInterface $relyingParty)
    {
        $this->relyingParty = $relyingParty;
    }

    /**
     * @throws \RuntimeException
     *
     * @return \Fp\OpenIdBundle\RelyingParty\RelyingPartyInterface
     */
    protected function getRelyingParty()
    {
        if (false == $this->relyingParty) {
            throw new \RuntimeException('The relying party is required for the listener work, but it was not set. Seems like miss configuration');
        }

        return $this->relyingParty;
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
     */
    protected function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function requiresAuthentication(Request $request)
    {
        if (false == parent::requiresAuthentication($request)) {
            return false;
        }

        return $this->getRelyingParty()->supports($request);
    }
}