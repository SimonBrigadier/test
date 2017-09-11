<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir.'/formslib.php');
/**
 * Description of text_area_form
 *
 * @author XOR
 */
class text_area_form extends moodleform
{
    public function definition()
    {
        $mform =& $this->_form;
        $mform->addElement('textarea', 'content', '', array('rows' => 1, 'cols' => 40));
    }
    
}
