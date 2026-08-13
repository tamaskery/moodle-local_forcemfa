<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_forcemfa\local;

/**
 * Evaluates forced MFA for one authenticated current user.
 *
 * This service contains no routing, tenancy, authentication, or authorization
 * logic. Browser and web-service entry points share it to avoid policy drift.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class authenticated_user_enforcer {
    /** The policy does not cover this user. */
    public const RESULT_NOT_ENFORCED = 0;

    /** The user already has a qualifying factor. */
    public const RESULT_SATISFIED = 1;

    /** The user must be sent to factor setup. */
    public const RESULT_SETUP_REQUIRED = 2;

    /** Policy or rollout configuration cannot safely enforce setup. */
    public const RESULT_CONFIGURATION_UNAVAILABLE = 3;

    /** A site administrator is allowed through solely to repair configuration. */
    public const RESULT_REPAIR_ALLOWED = 4;

    /** @var policy_provider_interface Policy provider. */
    private readonly policy_provider_interface $policyprovider;

    /** @var qualifying_factor_checker_interface Qualifying factor checker. */
    private readonly qualifying_factor_checker_interface $factorchecker;

    /** @var rollout_configuration Rollout configuration validator. */
    private readonly rollout_configuration $configuration;

    /**
     * Constructor.
     *
     * @param policy_provider_interface $policyprovider
     * @param qualifying_factor_checker_interface $factorchecker
     * @param rollout_configuration $configuration
     */
    public function __construct(
        policy_provider_interface $policyprovider,
        qualifying_factor_checker_interface $factorchecker,
        rollout_configuration $configuration,
    ) {
        $this->policyprovider = $policyprovider;
        $this->factorchecker = $factorchecker;
        $this->configuration = $configuration;
    }

    /**
     * Creates the production service graph.
     *
     * @return self
     */
    public static function create(): self {
        $factorchecker = new qualifying_factor_checker();
        return new self(
            new global_policy_provider(),
            $factorchecker,
            new rollout_configuration($factorchecker),
        );
    }

    /**
     * Returns the enforcement result for the authenticated current user.
     *
     * @param \stdClass $user
     * @return int One of the RESULT_* constants.
     */
    public function evaluate(\stdClass $user): int {
        $policy = $this->policyprovider->get_decision($user);

        if (!$policy->is_valid()) {
            return $this->configuration_failure_result($user);
        }

        if (!$policy->is_enforced()) {
            return self::RESULT_NOT_ENFORCED;
        }

        if ($this->factorchecker->has_factor($user)) {
            return self::RESULT_SATISFIED;
        }

        if (
            !$this->configuration->is_usable() ||
            !$this->is_mfa_ready() ||
            !$this->factorchecker->can_setup_factor($user)
        ) {
            return $this->configuration_failure_result($user);
        }

        return self::RESULT_SETUP_REQUIRED;
    }

    /**
     * Enforces a non-page request without redirects or return-URL state.
     *
     * @param \stdClass $user
     * @return void
     */
    public function enforce_non_page(\stdClass $user): void {
        $this->enforce_non_page_result($this->evaluate($user));
    }

    /**
     * Converts an evaluated result into a non-page transport exception.
     *
     * @param int $result One of the RESULT_* constants.
     * @return void
     */
    public function enforce_non_page_result(int $result): void {
        if ($result === self::RESULT_CONFIGURATION_UNAVAILABLE) {
            throw new \moodle_exception('errorconfigurationunavailable', 'local_forcemfa');
        }

        if ($result === self::RESULT_SETUP_REQUIRED) {
            $setupurl = new \moodle_url('/admin/tool/mfa/user_preferences.php');
            throw new \moodle_exception(
                'errorsetuprequired',
                'local_forcemfa',
                '',
                $setupurl->out(false),
            );
        }
    }

    /**
     * Returns the fail-safe result, preserving site-administrator repair access.
     *
     * @param \stdClass $user
     * @return int
     */
    private function configuration_failure_result(\stdClass $user): int {
        if (is_siteadmin($user->id)) {
            return self::RESULT_REPAIR_ALLOWED;
        }

        return self::RESULT_CONFIGURATION_UNAVAILABLE;
    }

    /**
     * Returns whether Moodle MFA is ready for the current user.
     *
     * @return bool
     */
    protected function is_mfa_ready(): bool {
        return \tool_mfa\manager::is_ready();
    }
}
