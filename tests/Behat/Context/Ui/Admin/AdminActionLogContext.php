<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Behat\Context\Ui\Admin;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use AKAWAKA\SyliusSecurityPlugin\Repository\AdminActionLogRepositoryInterface;
use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use FriendsOfBehat\SymfonyExtension\Mink\MinkParameters;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class AdminActionLogContext implements Context
{
    /**
     * @param AdminActionLogRepositoryInterface<AdminActionLogInterface> $adminActionLogRepository
     * @param FactoryInterface<AdminActionLogInterface> $adminActionLogFactory
     */
    public function __construct(
        private readonly Session $session,
        private readonly MinkParameters $minkParameters,
        private readonly RouterInterface $router,
        private readonly AdminActionLogRepositoryInterface $adminActionLogRepository,
        private readonly FactoryInterface $adminActionLogFactory,
        private readonly Command $purgeAdminActionLogsCommand,
    ) {
    }

    /**
     * @Given there is an administrator action log on the :route route dated :days days ago
     */
    public function thereIsAnAdministratorActionLogDatedDaysAgo(string $route, int $days): void
    {
        $log = $this->adminActionLogFactory->createNew();
        $log->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', $days)));
        $log->setRoute($route);
        $log->setUri('/admin');
        $log->setAdminEmail('sylius@example.com');

        $this->adminActionLogRepository->add($log);
    }

    /**
     * @Given there are :count administrator action logs
     */
    public function thereAreAdministratorActionLogs(int $count): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $this->thereIsAnAdministratorActionLogDatedDaysAgo(sprintf('app_admin_page_%d', $i), 1);
        }
    }

    /**
     * @When I go to the page :page of the administrator actions
     */
    public function iGoToThePageOfTheAdministratorActions(int $page): void
    {
        $this->session->getPage()->find('css', '.sylius-grid-nav__pagination')?->clickLink((string) $page);
    }

    /**
     * @Then I should see :count administrator actions in the list
     */
    public function iShouldSeeAdministratorActionsInTheList(int $count): void
    {
        Assert::same($this->session->getStatusCode(), 200);
        Assert::count($this->session->getPage()->findAll('css', 'table tbody tr'), $count);
    }

    /**
     * @Then I should see the pagination of the administrator actions
     */
    public function iShouldSeeThePaginationOfTheAdministratorActions(): void
    {
        Assert::notNull($this->session->getPage()->find('css', '.sylius-grid-nav__pagination .pagination'));
    }

    /**
     * @When I try to log in to the administration as :email with the password :password
     */
    public function iTryToLogInToTheAdministration(string $email, string $password): void
    {
        $this->visit('sylius_admin_login');

        $page = $this->session->getPage();
        $page->fillField('_username', $email);
        $page->fillField('_password', $password);
        $page->find('css', 'form button[type="submit"]')?->press();
    }

    /**
     * @When I purge the administrator action logs older than :days days
     */
    public function iPurgeTheAdministratorActionLogsOlderThanDays(int $days): void
    {
        $commandTester = new CommandTester($this->purgeAdminActionLogsCommand);
        $commandTester->execute(['--days' => (string) $days]);
        $commandTester->assertCommandIsSuccessful();
    }

    /**
     * @When I browse the administrator actions
     */
    public function iBrowseTheAdministratorActions(): void
    {
        $this->visit('akawaka_sylius_security_admin_admin_action_log_index');
    }

    /**
     * @Then a :type administrator action on the :route route should have been logged for :email
     */
    public function anAdministratorActionShouldHaveBeenLogged(string $type, string $route, string $email): void
    {
        Assert::notNull(
            $this->adminActionLogRepository->findOneBy(['type' => $type, 'route' => $route, 'adminEmail' => $email]),
            sprintf('No "%s" action on the "%s" route has been logged for "%s".', $type, $route, $email),
        );
    }

    /**
     * @Then no administrator action on the :route route should have been logged
     */
    public function noAdministratorActionShouldHaveBeenLogged(string $route): void
    {
        Assert::isEmpty($this->adminActionLogRepository->findBy(['route' => $route]));
    }

    /**
     * @Then there should be only an administrator action log on the :route route
     */
    public function thereShouldBeOnlyAnAdministratorActionLogOnTheRoute(string $route): void
    {
        $logs = $this->adminActionLogRepository->findAll();

        Assert::count($logs, 1);
        Assert::same($logs[0]->getRoute(), $route);
    }

    /**
     * @Then I should see the :route route in the administrator actions list
     */
    public function iShouldSeeTheRouteInTheAdministratorActionsList(string $route): void
    {
        Assert::same($this->session->getStatusCode(), 200);
        Assert::contains((string) $this->session->getPage()->find('css', 'table')?->getText(), $route);
    }

    /**
     * @When I show the first administrator action on the :route route
     */
    public function iShowTheFirstAdministratorActionOnTheRoute(string $route): void
    {
        $log = $this->adminActionLogRepository->findOneBy(['route' => $route]);
        Assert::isInstanceOf($log, AdminActionLogInterface::class);

        $this->visit('akawaka_sylius_security_admin_admin_action_log_show', ['id' => $log->getId()]);
    }

    /**
     * @Then I should see the administrator action details of :email
     */
    public function iShouldSeeTheAdministratorActionDetailsOf(string $email): void
    {
        Assert::same($this->session->getStatusCode(), 200);
        Assert::contains((string) $this->session->getPage()->find('css', '#akawaka-admin-action-log')?->getText(), $email);
    }

    /** @param array<string, mixed> $parameters */
    private function visit(string $route, array $parameters = []): void
    {
        /** @var string $baseUrl */
        $baseUrl = $this->minkParameters['base_url'];

        $this->session->visit(rtrim($baseUrl, '/') . $this->router->generate($route, $parameters));
    }
}
