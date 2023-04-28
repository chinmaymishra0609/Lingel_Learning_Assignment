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

/**
 * User sign-up form.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once('lib.php');

class login_signup_form extends moodleform implements renderable, templatable {
    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        $mform->addElement('text', 'username', get_string('signup_username_label', 'theme_nextgenis'), 'maxlength="100" size="30" placeholder="'. get_string('signup_username_placeholder', 'theme_nextgenis') .'"');
        $mform->setType('username', core_user::get_property_type('username'));
        $mform->addRule('username', get_string('signup_username_message', 'theme_nextgenis'), 'required', null, 'client');
        
        $mform->addElement('text', 'firstname', get_string('signup_firstname_label', 'theme_nextgenis'), 'maxlength="100" size="30" placeholder="'. get_string('signup_firstname_placeholder', 'theme_nextgenis') .'"');
        $mform->setType('firstname', core_user::get_property_type('firstname'));
        $mform->addRule('firstname', get_string('signup_firstname_message', 'theme_nextgenis'), 'required', null, 'client');
        
        $mform->addElement('text', 'lastname', get_string('signup_lastname_label', 'theme_nextgenis'), 'maxlength="100" size="30" placeholder="'. get_string('signup_lastname_placeholder', 'theme_nextgenis') .'"');
        $mform->setType('lastname', core_user::get_property_type('firstname'));
        $mform->addRule('lastname', get_string('signup_lastname_message', 'theme_nextgenis'), 'required', null, 'client');

        $mform->addElement('text', 'email', get_string('signup_email_label', 'theme_nextgenis'), 'maxlength="100" size="25" placeholder="'. get_string('signup_email_placeholder', 'theme_nextgenis') .'"');
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('signup_email_message', 'theme_nextgenis'), 'required', null, 'client');
        $mform->setForceLtr('email');

        $mform->addElement('text', 'email2', get_string('signup_confirm_email_label', 'theme_nextgenis'), 'maxlength="100" size="25" placeholder="'. get_string('signup_confirm_email_placeholder', 'theme_nextgenis') .'"');
        $mform->setType('email2', core_user::get_property_type('email'));
        $mform->addRule('email2', get_string('signup_confirm_email_message', 'theme_nextgenis'), 'required', null, 'client');
        $mform->setForceLtr('email2');
        
        $mform->addElement('hidden', 'username');
        $mform->setType('username', core_user::get_property_type('username'));

        $mform->addElement('text', 'phone1', get_string('signup_phone1_label', 'theme_nextgenis'), 'maxlength="100" size="12" autocapitalize="none"');
        $mform->setType('phone1', core_user::get_property_type('phone1'));
        $mform->addRule('phone1', get_string('signup_phone1_message', 'theme_nextgenis'), 'required', null, 'client');
        
        $mform->addElement('password', 'password', get_string('signup_password_label', 'theme_nextgenis'), [
            'maxlength' => 32,
            'size' => 12,
            'autocomplete' => 'new-password',
            'placeholder' => get_string('signup_password_placeholder', 'theme_nextgenis')
        ]);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('signup_password_message', 'theme_nextgenis'), 'required', null, 'client');

        $mform->addElement('password', 'confirmpassword', get_string('signup_confirm_password_label', 'theme_nextgenis'), [
            'maxlength' => 32,
            'size' => 12,
            'autocomplete' => 'new-password',
            'placeholder' => get_string('signup_confirm_password_placeholder', 'theme_nextgenis')
        ]);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('signup_confirm_password_message', 'theme_nextgenis'), 'required', null, 'client');
        
        // echo "<pre>"; print_r($mform); die();

        profile_signup_fields($mform);

        if (signup_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        // Hook for plugins to extend form definition.
        core_login_extend_signup_form($mform);

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // it can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // buttons
        $this->set_display_vertical();
        $this->add_action_buttons(false, get_string('signup_button', 'theme_nextgenis'));

    }

    function definition_after_data(){
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Extend validation for any form extensions from plugins.
        $errors = array_merge($errors, core_login_validate_extend_signup_form($data));

        if (signup_captcha_enabled()) {
            $recaptchaelement = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (!$recaptchaelement->verify($response)) {
                    $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
                }
            } else {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            }
        }

        $errors += signup_validate_data($data, $files);

        return $errors;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        $context = [
            'formhtml' => $formhtml
        ];
        return $context;
    }
}
