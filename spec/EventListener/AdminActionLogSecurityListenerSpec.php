<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\EventListener;

use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogFactoryInterface;
use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogWriterInterface;
use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class AdminActionLogSecurityListenerSpec extends ObjectBehavior
{
    function let(AdminActionLogFactoryInterface $factory, AdminActionLogWriterInterface $writer): void
    {
        $this->beConstructedWith($factory, $writer, ['admin']);
    }

    function it_logs_interactive_logins_of_administrators(
        InteractiveAuthenticatorInterface $authenticator,
        TokenInterface $token,
        AdminUserInterface $adminUser,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
        AdminActionLogInterface $log,
    ): void {
        $authenticator->isInteractive()->willReturn(true);
        $token->getUser()->willReturn($adminUser);
        $request = new Request();

        $factory->createFromRequest(AdminActionLogInterface::TYPE_LOGIN, $request, $adminUser, null)->willReturn($log);
        $writer->write($log)->shouldBeCalled();

        $this->onLoginSuccess(new LoginSuccessEvent(
            $authenticator->getWrappedObject(),
            new SelfValidatingPassport(new UserBadge('admin@example.com', fn () => $adminUser->getWrappedObject())),
            $token->getWrappedObject(),
            $request,
            null,
            'admin',
        ));
    }

    function it_does_not_log_stateless_authentications(
        AuthenticatorInterface $authenticator,
        TokenInterface $token,
        AdminUserInterface $adminUser,
        AdminActionLogWriterInterface $writer,
    ): void {
        $token->getUser()->willReturn($adminUser);

        $writer->write(Argument::any())->shouldNotBeCalled();

        $this->onLoginSuccess(new LoginSuccessEvent(
            $authenticator->getWrappedObject(),
            new SelfValidatingPassport(new UserBadge('admin@example.com', fn () => $adminUser->getWrappedObject())),
            $token->getWrappedObject(),
            new Request(),
            null,
            'new_api_admin_user',
        ));
    }

    function it_logs_failed_logins_on_the_configured_firewalls(
        AuthenticatorInterface $authenticator,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
        AdminActionLogInterface $log,
    ): void {
        $request = new Request();

        $factory->createFromRequest(AdminActionLogInterface::TYPE_LOGIN_FAILURE, $request, null, 'admin@example.com')->willReturn($log);
        $writer->write($log)->shouldBeCalled();

        $this->onLoginFailure(new LoginFailureEvent(
            new BadCredentialsException(),
            $authenticator->getWrappedObject(),
            $request,
            null,
            'admin',
            new Passport(new UserBadge('admin@example.com'), new PasswordCredentials('wrong')),
        ));
    }

    function it_does_not_log_failed_logins_on_other_firewalls(AuthenticatorInterface $authenticator, AdminActionLogWriterInterface $writer): void
    {
        $writer->write(Argument::any())->shouldNotBeCalled();

        $this->onLoginFailure(new LoginFailureEvent(new BadCredentialsException(), $authenticator->getWrappedObject(), new Request(), null, 'shop'));
    }

    function it_logs_logouts_of_administrators(
        TokenInterface $token,
        AdminUserInterface $adminUser,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
        AdminActionLogInterface $log,
    ): void {
        $token->getUser()->willReturn($adminUser);
        $request = new Request();

        $factory->createFromRequest(AdminActionLogInterface::TYPE_LOGOUT, $request, $adminUser, null)->willReturn($log);
        $writer->write($log)->shouldBeCalled();

        $this->onLogout(new LogoutEvent($request, $token->getWrappedObject()));
    }
}
