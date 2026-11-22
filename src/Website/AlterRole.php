<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, TrailSubject, Website, WebsiteComponents};
use PhpPgAdmin\Application\DTO\Role as DTORole;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\Logins;

final class AlterRole extends CreateRole
{
    #[\Override]
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = Website::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, [TrailSubject::Server, TrailSubject::Role]));

        $serverId = RequestParameter::getString('server') ?? '';
        $rolename = RequestParameter::getString('rolename');

        $h2 = $dom->createElement('h2');
        $h2->appendChild($dom->createTextNode(_('Alter')));
        $helpLink = WebsiteComponents::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => 'pg.role.alter',
                'server' => $serverId,
            ],
        );
        $h2->appendChild($helpLink);
        $body->appendChild($h2);

        if ($this->message !== '') {
            $body->appendChild(WebsiteComponents::buildMessage($dom, $this->message));
        }

        $form = $dom->createElement('form');
        $form->setAttribute('method', 'post');

        $role = null;

        if (!is_null($rolename)) {
            $serverSession = Logins::serverSession($serverId, Config::getServers());
            $db = PhpPgAdminConnection::createFromServerSession($serverSession);
            $role = $db?->getRole($rolename);
        }

        // The form alters the role with this name, it cannot rename the role.
        $form->appendChild(self::buildCreateOrEditRoleTable($dom, $serverId, $role, isNameReadonly: true));
        $form->appendChild(self::buildCreateOrEditFormParagraphButtonsTable($dom, $serverId, $role));

        $body->append($form);

        return $body;
    }

    /**
     * The constructor inherited from the create page runs it in place of the create.
     *
     * @return ?string The page to lead to, or null to show this page again.
     */
    #[\Override]
    protected function handlePostRequest(): ?string
    {
        $roleFromForm = DTORole::createFromFormRequest();

        $password = RequestParameter::getString(Role::FORM_ID_PASSWORD) ?? '';
        $passwordConfirmation = RequestParameter::getString(Role::FORM_ID_PASSWORD_CONFIRMATION) ?? '';

        if ($password !== '' && $password !== $passwordConfirmation) {
            $this->message = _('Password does not match confirmation.');

            return null;
        }

        $serverId = RequestParameter::getString('server') ?? '';
        $serverSession = Logins::serverSession($serverId, Config::getServers());

        $formMemberOf = RequestParameter::getArray('memberof') ?? [];
        $formMembers = RequestParameter::getArray('members') ?? [];
        $formAdminMembers = RequestParameter::getArray('adminmembers') ?? [];

        $newMemberOf = [];
        $newMembers = [];
        $newAdminMembers = [];

        foreach ($formMemberOf as $member) {
            if (is_string($member)) {
                $newMemberOf[] = $member;
            }
        }

        foreach ($formMembers as $member) {
            if (is_string($member)) {
                $newMembers[] = $member;
            }
        }

        foreach ($formAdminMembers as $member) {
            if (is_string($member)) {
                $newAdminMembers[] = $member;
            }
        }

        $db = PhpPgAdminConnection::createFromServerSession($serverSession);

        if (is_null($db)) {
            return null;
        }

        try {
            $db->updateRole(
                role: $roleFromForm,
                password: $password,
                newMemberOf: $newMemberOf,
                newMembers: $newMembers,
                newAdminMembers: $newAdminMembers,
            );

            $redirectUrl = 'roles.php';
            $redirectUrlParams = [
                'message' => _('Role altered.'),
                'server' => $serverId,
                'subject' => 'server',
            ];

            return $redirectUrl . '?' . http_build_query($redirectUrlParams);
        } catch (\PDOException $e) {
            $this->message = _('Role alter failed.');
            $this->pdoException = $e;
        } catch (\Throwable) {
            $this->message = _('Role alter failed.');
        }

        return null;
    }
}
