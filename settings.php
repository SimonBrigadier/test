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
 * Settings for the PSTGU deanery
 *
 * @copyright 2016 Simon Brigadier
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   report_pstgu_deanery
 */

defined('MOODLE_INTERNAL') || die;


if ($ADMIN->fulltree) 
{
    $settings->add(new admin_setting_configtext('report_pstgu_deaneryAb', get_string('localisedDB_abit', 'report_report_pstgu_deanery'),
                       get_string('longlocalisedDB_abit', 'report_report_pstgu_deanery'), 'Абитуриенты_Т'));
    $settings->add(new admin_setting_configtext('report_pstgu_deaneryDec', get_string('localisedDB_dean', 'report_report_pstgu_deanery'),
                       get_string('longlocalisedDB_dean', 'report_report_pstgu_deanery'), 'Деканат_Т'));
    $settings->add(new admin_setting_configtext('report_pstgu_deanery_OP', get_string('localised_OP', 'report_report_pstgu_deanery'),
                       get_string('longlocalised_OP', 'report_report_pstgu_deanery'), 'ОП'));
    $settings->add(new admin_setting_configtext('report_pstgu_deanery_OK', get_string('localised_OK', 'report_report_pstgu_deanery'),
                       get_string('longlocalised_OK', 'report_report_pstgu_deanery'), 'ОК'));
    $k=0;
    $settings->add(new admin_setting_heading('heading', 'Видимость вкладок плагина', 'Для отображения нужных вкладок поставьте галочек'));
    
    
    for($i = 1; true; $i++)
    {
        if(file_exists($CFG->dirroot . "/report/report_pstgu_deanery/export_ispk$i.php"))
        {
            $settings->add(new admin_setting_configcheckbox("export_ispk$i", get_string('tab'.$i.'name', 'report_report_pstgu_deanery'),
                       '', 1));
        }
        else
            break;
    }
}


