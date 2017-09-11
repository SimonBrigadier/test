<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once $CFG->libdir.'/formslib.php';
class grade_date_form extends moodleform 
{
    function definition()
    {
        $mform    = $this->_form;        
        $mform->addElement('date_selector', 'gradedate', '', array('optional' => true));
        $mform->disabledIf('date_selector', 'gradedateselect','eq', '0');
    }
}