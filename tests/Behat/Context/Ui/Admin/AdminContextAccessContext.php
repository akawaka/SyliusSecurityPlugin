<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Doctrine\Persistence\ObjectManager;
use FriendsOfBehat\SymfonyExtension\Mink\MinkParameters;
use Sylius\Behat\Service\SecurityServiceInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class AdminContextAccessContext implements Context
{
    /** @param UserRepositoryInterface<AdminUserInterface> $adminUserRepository */
    public function __construct(
        private readonly Session $session,
        private readonly MinkParameters $minkParameters,
        private readonly RouterInterface $router,
        private readonly SharedStorageInterface $sharedStorage,
        private readonly SecurityServiceInterface $adminSecurityService,
        private readonly ExampleFactoryInterface $adminUserFactory,
        private readonly UserRepositoryInterface $adminUserRepository,
        private readonly ObjectManager $adminUserManager,
    ) {
    }

    /**
     * @Given there is an administrator :email with no context role
     * @Given there is an administrator :email with the :roles roles
     */
    public function thereIsAnAdministratorWithRoles(string $email, string $roles = ''): void
    {
        $this->createAdministrator($email, $roles);
    }

    /**
     * @Given I am logged in as an administrator with no context role
     * @Given I am logged in as an administrator with the :roles roles
     */
    public function iAmLoggedInAsAnAdministratorWithRoles(string $roles = ''): void
    {
        $adminUser = $this->createAdministrator('sylius@example.com', $roles);

        $this->adminSecurityService->logIn($adminUser);
        $this->sharedStorage->set('administrator', $adminUser);
    }

    /**
     * @When I try to open the :route admin page
     */
    public function iTryToOpenTheAdminPage(string $route): void
    {
        $this->visit($route);
    }

    /**
     * @When I open my account page
     */
    public function iOpenMyAccountPage(): void
    {
        /** @var AdminUserInterface $adminUser */
        $adminUser = $this->sharedStorage->get('administrator');

        $this->visit('sylius_admin_admin_user_update', ['id' => $adminUser->getId()]);
    }

    /**
     * @When I try to open the account page of the administrator :email
     */
    public function iTryToOpenTheAccountPageOfTheAdministrator(string $email): void
    {
        $adminUser = $this->adminUserRepository->findOneByEmail($email);
        Assert::isInstanceOf($adminUser, AdminUserInterface::class);

        $this->visit('sylius_admin_admin_user_update', ['id' => $adminUser->getId()]);
    }

    /**
     * @When I change my first name to :firstName and save
     */
    public function iChangeMyFirstNameAndSave(string $firstName): void
    {
        $page = $this->session->getPage();
        $page->fillField('sylius_admin_user_firstName', $firstName);
        $page->pressButton('sylius_save_changes_button');
    }

    /**
     * @Then I should not be able to change roles
     */
    public function iShouldNotBeAbleToChangeRoles(): void
    {
        Assert::same($this->session->getStatusCode(), 200);
        Assert::null($this->session->getPage()->find('css', 'input[name^="sylius_admin_user[securityRoles]"]'));
    }

    /**
     * @Then I should be back on my account page
     */
    public function iShouldBeBackOnMyAccountPage(): void
    {
        /** @var AdminUserInterface $adminUser */
        $adminUser = $this->sharedStorage->get('administrator');

        Assert::same($this->session->getStatusCode(), 200);
        Assert::endsWith(
            (string) parse_url($this->session->getCurrentUrl(), \PHP_URL_PATH),
            $this->router->generate('sylius_admin_admin_user_update', ['id' => $adminUser->getId()]),
        );
    }

    /**
     * @Then my first name should be :firstName
     */
    public function myFirstNameShouldBe(string $firstName): void
    {
        /** @var AdminUserInterface $adminUser */
        $adminUser = $this->sharedStorage->get('administrator');
        $this->adminUserManager->refresh($adminUser);

        Assert::same($adminUser->getFirstName(), $firstName);
    }

    /**
     * @When I assign the :roles roles to the administrator :email
     */
    public function iAssignTheRolesToTheAdministrator(string $roles, string $email): void
    {
        $adminUser = $this->adminUserRepository->findOneByEmail($email);
        Assert::isInstanceOf($adminUser, AdminUserInterface::class);

        $this->visit('sylius_admin_admin_user_update', ['id' => $adminUser->getId()]);

        $page = $this->session->getPage();
        foreach ($this->parseRoles($roles) as $role) {
            $checkbox = $page->find('css', sprintf('input[name="sylius_admin_user[securityRoles][]"][value="%s"]', $role));
            Assert::notNull($checkbox, sprintf('The "%s" role checkbox should be displayed.', $role));
            $checkbox->check();
        }
        $page->pressButton('sylius_save_changes_button');
    }

    /**
     * @Then I should be granted access
     */
    public function iShouldBeGrantedAccess(): void
    {
        Assert::same($this->session->getStatusCode(), 200);
    }

    /**
     * @Then I should be denied access
     */
    public function iShouldBeDeniedAccess(): void
    {
        Assert::same($this->session->getStatusCode(), 403);
    }

    /**
     * @Then I should see only the :sections sections in the main menu
     */
    public function iShouldSeeOnlyTheSectionsInTheMainMenu(string $sections): void
    {
        $this->visit('sylius_admin_dashboard');

        $headers = array_map(
            static fn (NodeElement $header): string => trim($header->getText()),
            $this->session->getPage()->findAll('css', '.admin-layout__sidebar .header'),
        );
        $headers = array_values(array_diff($headers, ['Official support', 'Administration']));

        Assert::same($headers, '' === $sections ? [] : array_map('trim', explode(',', $sections)));
    }

    /**
     * @Then the administrator :email should have the :roles roles
     */
    public function theAdministratorShouldHaveTheRoles(string $email, string $roles): void
    {
        $adminUser = $this->adminUserRepository->findOneByEmail($email);
        Assert::isInstanceOf($adminUser, AdminUserInterface::class);
        $this->adminUserManager->refresh($adminUser);

        foreach ($this->parseRoles($roles) as $role) {
            Assert::true($adminUser->hasRole($role), sprintf('Administrator "%s" should have the "%s" role.', $email, $role));
        }
    }

    /** @param array<string, mixed> $parameters */
    private function visit(string $route, array $parameters = []): void
    {
        /** @var string $baseUrl */
        $baseUrl = $this->minkParameters['base_url'];

        // The session cookie is set on the Mink base URL, so pages must be visited on it
        $this->session->visit(rtrim($baseUrl, '/') . $this->router->generate($route, $parameters));
    }

    private function createAdministrator(string $email, string $roles): AdminUserInterface
    {
        /** @var AdminUserInterface $adminUser */
        $adminUser = $this->adminUserFactory->create(['email' => $email, 'password' => 'sylius', 'api' => true]);
        foreach ($this->parseRoles($roles) as $role) {
            $adminUser->addRole($role);
        }

        $this->adminUserRepository->add($adminUser);

        return $adminUser;
    }

    /** @return list<string> */
    private function parseRoles(string $roles): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $roles))));
    }
}
