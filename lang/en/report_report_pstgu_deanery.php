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
 * Lang strings.
 *
 * Language strings to be used by report/report_pstgu_deanery
 *
 * @package    report_report_pstgu_deanery
 * @copyright  2017 Simon Brigadier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['report_pstgu_deanery:view'] = 'To use a plugin Deanery of IDO';
$string['report_pstgu_deanery:view'] = 'use plagin';
$string['report_pstgu_deanery_tab1:view'] = 'use tab1';
$string['report_pstgu_deanery_tab2:view'] = 'use tab2';
$string['report_pstgu_deanery_tab3:view'] = 'use tab3';
$string['report_pstgu_deanery_tab4:view'] = 'use tab4';
$string['report_pstgu_deanery_tab5:view'] = 'use tab5';
/**
 * tab 2
 */
$string['tab2name'] = 'Enrolling to cohorts';
$string['pluginname'] = 'Deanery of SDO';
$string['execact'] = 'Execute an action';
$string['selectfirstusers'] = 'Execute';
$string['descriptionactions'] = 'Description actions';
$string['helpinfotab2'] = 'Enrolling to cohorts';
$string['helpinfotab2_help'] = '<tab description>';
$string['helpsortfio'] = 'Sort by name ASC';
$string['helpsortfio_help'] = '<sort by name description>';
$string['helplabelactiontab2'] = 'Action';
$string['helplabelactiontab2_help'] = 'Description action';
$string['helpcohorttab2'] = 'Cohorts';
$string['helpcohorttab2_help'] = '<description>';
/**
 * actselect
 */
$string['actselectcreate'] = 'Create enrollee`s statement in IS PK';
$string['actselectdelete'] = 'Delete enrollee`s statement from IS PK';
$string['actselectupdate'] = 'Update enrollee`s data in IS PK';
$string['actselectaddtocohort'] = 'Enrolle to cohort';
$string['actselectremovefromcohort'] = 'Unenrolle from cohort';
/**
 * notification
 */
$string['unenrollcohortwarning'] = '<p>Action is executed.</p><p>Following students is not unenrolle from cohort {$a}, because they are not in it:</p>';
$string['errornotification'] = 'Action is not executed! The users are not chosen!';
$string['enrollcohortwarning'] = '<p>Action is executed.</p><p>Following students is not enrolle to cohort {$a}, because they are in it:</p>';
$string['errornotcipher'] = 'There are errors in course sortname.';
/**
 * tab 3
 */
$string['tab3name'] = 'Links with group IS Deanary';
$string['info'] = '<p>In the IS Deanery for each semester, its own set of groups. Therefore, each semester, it is necessary to establish an agreement between the training cohorts with SDO and groups in the IS Deanery.</p>';
$string['infoautosearch'] = '<p>2.For auto-search, between the SDO cohorts and IS groups, Deccan, select the school year and semester number in the Deanery IS and click the "Find Compliance" button:</p>';
$string['findcompliance'] = 'Find Compliance';
/**
 * tab 4
 */
$string['tab4name'] = 'Tab name';
$string['descriptionactionstab4'] = 'Tab description';
$string['helpinfotab4'] = 'Verify cohort members';
$string['helpinfotab4_help'] = '<p>Description</p>';
$string['idisdnotfound'] = '<p>Description</p>';
/**
 * tab 1
 */
$string['tab1name'] = 'Download in IS PK';
$string['descriptionactionstab1'] = 'Download/update student`s private information and create application in IS PK PSTGU';
/**
 * help
 */
$string['helpinfotab1'] = 'Download to IS PK';
$string['helpinfotab1_help'] = '<tab description>';
$string['helpcipher'] = 'Programm cipher';
$string['helpcipher_help'] = '<programm cipher description>';
$string['helpactselect'] = 'Action with students';
$string['helpactselect_help'] = '<action description>';
$string['cohortselectioninfo'] = 'Cohorts';
$string['cohortselectioninfo_help'] = '<p>description</p>';
$string['resultverifyinfo'] = 'Cohort verify';
$string['resultverifyinfo_help'] = '<p>description</p>';
/**
 * Вкладка 5
 */
$string['tab5name'] = 'Download grade';
$string['tab5info'] = 'description';
$string['1tab5'] = 'description';
$string['2tab5'] = 'description';
$string['3tab5'] = 'description';
$string['4tab5'] = 'description';
$string['5tab5'] = 'description';
$string['tabinfodesc'] = 'description';
$string['tabinfodesc_help'] = 'description';
$string['cohortinfo'] = 'description';
$string['cohortinfo_help'] = 'description';
$string['subjinfo'] = 'Поле Дисциплина';
$string['subjinfo_help'] = 'description';
$string['spreadsheet'] = 'description';
$string['spreadsheet_help'] = 'description';
$string['spreadsheettype'] = 'description';
$string['spreadsheettype_help'] = 'description';
$string['gradedate'] = 'description';
$string['gradedate_help'] = 'description';
$string['verifyres'] = 'description' ; 
$string['verifyres_help'] = 'description';
$string['errornotfound'] = 'description';
$string['errorwrongtype'] = 'description';
$string['errorclosed'] = 'description №{$a} description';
$string['errornotone'] = 'description {$a->year} description {$a->cohort} description {$a->subj} description';
//$a = (object)array('year' => $year, 'cohort' => $cohortname, 'subj' => $subjname);get_string('cliincorrectvalueerror', 'admin', $a);
$string['localisedDB_abit'] = 'DB for "ИС Абитуриенты"';
$string['localisedDB_dean'] = 'DB for "ИС Деканат"';
$string['longlocalisedDB_abit'] = 'description';
$string['longlocalisedDB_dean'] = 'description';
$string['longlocalised_OP'] = 'description';
$string['longlocalised_OK'] = 'description';
$string['localised_OP'] = 'description';
$string['localised_OK'] = 'description';