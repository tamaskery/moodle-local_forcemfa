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

use tool_mfa\plugininfo\factor;

/**
 * Validates the supported tool_mfa first-factor rollout configuration.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rollout_configuration {
    /** Moodle MFA is disabled. */
    public const ISSUE_TOOL_MFA_DISABLED = 'toolmfadisabled';

    /** There is no qualifying factor users can configure. */
    public const ISSUE_NO_QUALIFYING_FACTOR = 'noqualifyingfactor';

    /** The no-setup bootstrap factor is disabled. */
    public const ISSUE_NOSETUP_DISABLED = 'nosetupdisabled';

    /** The no-setup bootstrap factor cannot pass by itself. */
    public const ISSUE_NOSETUP_WEIGHT = 'nosetupweight';

    /** @var qualifying_factor_checker_interface Qualifying factor checker. */
    private readonly qualifying_factor_checker_interface $factorchecker;

    /**
     * Constructor.
     *
     * @param qualifying_factor_checker_interface $factorchecker
     */
    public function __construct(
        qualifying_factor_checker_interface $factorchecker,
    ) {
        $this->factorchecker = $factorchecker;
    }

    /**
     * Returns configuration issue identifiers.
     *
     * @return string[]
     */
    public function get_issues(): array {
        $issues = [];

        if (!get_config('tool_mfa', 'enabled')) {
            $issues[] = self::ISSUE_TOOL_MFA_DISABLED;
        }

        if (!$this->factorchecker->has_available_factor()) {
            $issues[] = self::ISSUE_NO_QUALIFYING_FACTOR;
        }

        $nosetup = factor::get_factor('nosetup');
        if (!$nosetup || !$nosetup->is_enabled()) {
            $issues[] = self::ISSUE_NOSETUP_DISABLED;
        } else if ($nosetup->get_weight() < 100) {
            $issues[] = self::ISSUE_NOSETUP_WEIGHT;
        }

        return $issues;
    }

    /**
     * Returns whether the supported rollout configuration is usable.
     *
     * @return bool
     */
    public function is_usable(): bool {
        return empty($this->get_issues());
    }
}
