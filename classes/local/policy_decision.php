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
 * Immutable forced MFA policy decision for one user.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class policy_decision {
    /** @var bool Whether the stored policy is valid. */
    private readonly bool $valid;

    /** @var bool Whether the policy covers the supplied user. */
    private readonly bool $enforced;

    /**
     * Constructor.
     *
     * @param bool $valid
     * @param bool $enforced
     */
    private function __construct(bool $valid, bool $enforced) {
        $this->valid = $valid;
        $this->enforced = $enforced;
    }

    /**
     * Returns a decision for a valid policy.
     *
     * @param bool $enforced
     * @return self
     */
    public static function valid(bool $enforced): self {
        return new self(true, $enforced);
    }

    /**
     * Returns an invalid-configuration decision.
     *
     * @return self
     */
    public static function invalid(): self {
        return new self(false, false);
    }

    /**
     * Returns whether the stored policy is valid.
     *
     * @return bool
     */
    public function is_valid(): bool {
        return $this->valid;
    }

    /**
     * Returns whether the policy covers the supplied user.
     *
     * @return bool
     */
    public function is_enforced(): bool {
        return $this->valid && $this->enforced;
    }
}
