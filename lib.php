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
 * Public API of the log report.
 *
 * Defines the APIs used by log reports
 *
 * @package    report_log
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Completion Progress block common configuration and helper functions
 * 
 * @package    report_report_pstgu_deanery
 * @copyright  2017 Simon Brigadier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
/**
 * Добавляет ссылку на плагин деканата в узел "Отчеты" в блоке навигации курса
 * 
 * @param stdClass $navigation
 * @param stdClass $course
 * @param stdClass $context
 */
function report_report_pstgu_deanery_extend_navigation_course($navigation, $course, $context) 
{
    if (has_capability('report/report_pstgu_deanery:view', $context)) 
    {
        //формируем ссылку для добавления в блок навигации по курсу
        $url = new moodle_url('/report/report_pstgu_deanery/index.php', array('id'=>$course->id));
        //добавляем ссылку в блок навигации
        $navigation->add(get_string('pluginname', 'report_report_pstgu_deanery'), $url, navigation_node::TYPE_ACTIVITY, null, null, new pix_icon('i/report', ''));
    }
}
/**
 * This function extends the module navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $cm
 */
function report_report_pstgu_deanery_extend_navigation_module($navigation, $cm) {
    if (has_capability('report/report_pstgu_deanery:view', context_course::instance($cm->course))) {
        $url = new moodle_url('/report/report_pstgu_deanery/index.php', array('chooselog'=>'1','id'=>$cm->course,'modid'=>$cm->id));
        $navigation->add(get_string('pluginname', 'report_report_pstgu_deanery'), $url, navigation_node::TYPE_SETTING, null, 'pstgu_deanery');
    }
}
/**
 * Формирует XML-строку для запроса
 * 
 * @param stdClass $res Студенты, котороых выбрали
 * @param stdClass $creator Пользователь, который произвел данный экпорт
 * @param stdClass $cipherProg Глобальная группа для шифра программы
 * @param string $Action Строка с названием действия
 * @return string XML-строка
 */
function GetXML($res, $creator, $cipherProg, $Action)
{
    //добавляем название тегов
//    $Students = 'Абитуриенты';
//    $Student = 'Абитуриент';
//    $ID = 'ид_в_СДО';
//    $lastname = 'Фамилия';
//    $firstname = 'Имя';
//    $middlename = 'Отчество';
//    $email = 'email';
//    $phone1 = 'Телефон';
//    $phone2 = 'Мобильный';
    $Students = 'Абитуриенты';
    $Student = 'Абитуриент';
    $ID = 'ид_в_СДО';
    $lastname = 'Фамилия';
    $firstname = 'Имя';
    $middlename = 'Отчество';
    $email = 'email';
    $phone1 = 'Телефон';
    $phone2 = 'Мобильный';
    $text = '';
    $rootNode = 'ЗапросСДО';//ЗапросСДО
    $operator = 'Operator';//Оператор
    $fd = fopen("XMLdebugIN.xml", 'w') or die("не удалось создать файл");
    //создание документа
    $xml = new XMLWriter();
    //для записи в буфeр
    $xml->openMemory();    
    //прописываем декларацию
    $xml->startDocument('1.0'/*, 'UTF-8'*/);
    //устанавливаем языковые настройки локали для парсинга
    setlocale(LC_ALL, 'ru_RU.utf8');
    //разделили на массив из строк
    $arr = preg_split('/[^\w]+/u',$cipherProg);
    if($arr[0] == "ПК" || $arr[0] == "ОК")
    {
        // удалили лишние пробелы
        $cipher = preg_replace("/\s{2,}/",' ',$arr[1]);
        $year = preg_replace("/\s{2,}/",' ',$arr[2]);
    }
    else
    {
        // удалили лишние пробелы
        $cipher = preg_replace("/\s{2,}/",' ',$arr[0]);
        $year = preg_replace("/\s{2,}/",' ',$arr[1]);
    }
    $xml->startElement($rootNode);
    $text .= $xml->outputMemory();
    $xml->writeElement($operator,"$creator->lastname $creator->firstname");
    $text .= $xml->outputMemory() . "\r\n";
    // когда 
    $date = date('d.m.Y H:i:s');
    $xml->writeElement('ДатаЗапроса',"$date");//ДатаЗапроса
    $text .= $xml->outputMemory() . "\r\n";
    $xml->writeElement('Действие', $Action);    //Действие
    $text .= $xml->outputMemory() . "\r\n";
    $xml->writeElement('УчебныйГодНабора', $year);    //УчебныйГодНабора
    $text .= $xml->outputMemory() . "\r\n";
    $xml->writeElement('ШифрПрограммы', $cipher);//ШифрПрограммы
    $text .= $xml->outputMemory() . "\r\n";
    //открываем тег Students
    $xml->startElement($Students);
    $text .= $xml->outputMemory();
    $id = -1;
    foreach($res as $row)
    {
        //echo $row->id . ' ' . $row->lastname .  ' ' . $row->firstname . ' ' . $row->middlename . ' ' . $row->email . ' ' . $row->phone1 . ' ' . $row->phone2 . ' ' . $row->shortname . ' ' . strip_tags( $row->data ) . '<br>';
        //если студент новый, добавляем тег Student
        if($id != $row->id)
        {   
            if($id > 0)
            {
                //закрыли тег Student
                $xml->endElement();
                $text .= "    " . $xml->outputMemory() . "\r\n";
            }        
            //записываем статические поля
            $xml->startElement($Student);
            $xml->writeAttribute($ID, $row->id);        
            $text .= $xml->outputMemory();
            $xml->writeElement($lastname, $row->lastname);
            $text .= $xml->outputMemory() . "\r\n";
            $name = preg_split('/[^\w]+/u', $row->firstname);
            $xml->writeElement($firstname, $name[0] );
            $text .= "       " . $xml->outputMemory() . "\r\n";
            if(isset($name[1]))
            {
                $xml->writeElement($middlename, $name[1]);      
                $text .= "       " . $xml->outputMemory() . "\r\n";
            }
            else
            {
                $xml->writeElement($middlename, ' ');      
                $text .= "       " . $xml->outputMemory() . "\r\n";
            }
            $xml->writeElement($email, $row->email);
            $text .= "       " . $xml->outputMemory() . "\r\n";
            $xml->writeElement($phone1, $row->phone1);
            $text .= "       " . $xml->outputMemory() . "\r\n";
            $xml->writeElement($phone2, $row->phone2);
            $text .= "       " . $xml->outputMemory() . "\r\n";
        } 
        $k = 0;
        //динамические поля
        if(isset($row->data) && $row->shortname != 'scandiploma' )// если данное поле установлено, то записываем и не скан диплома
        {
            switch($row->shortname)
            {
                case 'dateofbirth':
                    $xml->writeElement('ДатаРождения', date( 'd.m.Y', $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'placeofbirth':
                    $xml->writeElement('МестоРождения', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'malefemale':
                    $xml->writeElement('Пол', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'passportnumber':
                    $xml->writeElement('СерияНомерПаспорта', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'passportdate':
                    $xml->writeElement('ДатаВыдачиПаспорта', date( 'd.m.Y', $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'passportissuedby':
                    $xml->writeElement('КемВыданПаспорт', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'citizenship':
                    $xml->writeElement('Гражданство', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'priesthood':
                    $xml->writeElement('ДуховныйСан', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'postcode1':
                    $xml->writeElement('ИндексПрописки', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'country1':
                    $xml->writeElement('СтранаПрописки', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'region1':
                    $xml->writeElement('РегионПрописки', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'city1':
                    $xml->writeElement('ГородПрописки', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'street1':
                    $xml->writeElement('УлицаПрописки', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'house1':
                    $xml->writeElement('ДомКвартираПрописки', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'addressthesame':
                    $xml->writeElement('АдресПроживанияТотже', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'postecode2':
                    $xml->writeElement('ИндексПроживания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'state2':
                    $xml->writeElement('СтранаПроживания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'region2':
                    $xml->writeElement('РегионПроживания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'city2':
                    $xml->writeElement('ГородПроживания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'street2':
                    $xml->writeElement('УлицаПроживания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'house2':
                    $xml->writeElement('ДомКвартираПроживания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'diplomatype':
                    $xml->writeElement('ДокументОбОбразовании', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'institutiontype':
                    $xml->writeElement('ТипУчебногоЗаведения', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'institution':
                    $xml->writeElement('НазваниеУчебногоЗаведения', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'institutionaddress':
                    $xml->writeElement('АдресУчебногоЗаведения', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'institutecompletion':
                    $xml->writeElement('ГодОкончания', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'diplomaseries':
                    $xml->writeElement('СерияДиплома', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'diplomanumber':
                    $xml->writeElement('НомерДиплома', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'diplomadate':
                    $xml->writeElement('ДатаВыдачиДиплома', date( 'd.m.Y', $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'qualification':
                    $xml->writeElement('ТекущаяКвалификация', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'specialty':
                    $xml->writeElement('ТекущаяСпециальность', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
                case 'degree':
                    $xml->writeElement('УчёнаяСтепень', strip_tags( $row->data ));
                    $text .= "       " . $xml->outputMemory() . "\r\n";
                    break;
            }                      
        }
        //запомнили ид студента
        $id = $row->id;
    }
    //закрываем последний тег Student
    $xml->endElement();
    $text .= "    " . $xml->outputMemory() . "\r\n";
    //закрываем тег Students
    $xml->endElement();
    $text .= $xml->outputMemory() . "\r\n";
    //закрываем тег FileCDO
    $xml->endElement();
    $text .= $xml->outputMemory() . "\r\n";
    fwrite($fd, $text);
    fclose($fd);
    return $text;
}
/**
 * Производит подключение с MSSQL серверу и выполнаяет хранимую там процедуру
 * 
 * @param string $xmlData XML-строка со студентами, которую надо отправить на сервер
 * @return string XML-строка с номерами личных дел
 */
function ConnectMSSql($xmlData)
{

    $conn = edvp_connect('A');
    // для отправки ошибок [Абитуриенты_Т].dbo.
    $strerror = '';  
    $tsql_callSP = "{call sp_PSTGU_IDO_ImportAbit_Rus( ?, ?)}";  
    $xmlOut = "";
    $params = array(   
                     array($xmlData, SQLSRV_PARAM_IN),  
                     array(&$xmlOut, SQLSRV_PARAM_INOUT)  
                   );
    /* Execute the query. */  
    $stmt = sqlsrv_query( $conn, $tsql_callSP, $params); 
    // в случае ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors(); 
        sqlsrv_close( $conn);
        OutputErrors($err_info);        
    }

    // получили значение 
    sqlsrv_next_result($stmt);
    //читаем пришедший хмл
    $xmlREADER = new XMLReader();
    $xmlREADER->XML($xmlOut);
    $xmlREADER->read();
    //берем ид строки 
    $id = $xmlREADER->readString();
    // Строка селекта [Абитуриенты_Т].dbo.
    $tsql_callSP = "SELECT [Output_XML]
                    FROM v_PSTGU_CDO_ImportResult
                    WHERE [id] = ?";   
    $params = array(   
                    array($id, SQLSRV_PARAM_IN),                      
                    );
    //производим запрос
    $stmt = sqlsrv_query( $conn, $tsql_callSP, $params); 
    //случай ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors();  
        sqlsrv_close( $conn);
        OutputErrors($err_info);
    }
    $xmlOut = '';
    while( $row = sqlsrv_fetch_object( $stmt ))
    {
        $xmlOut = $row->Output_XML;
    }

    /*Free the statement and connection resources. */  
    sqlsrv_free_stmt( $stmt);  
    sqlsrv_close( $conn);
    return $xmlOut;
}
/**
 * Выводит ошибки при обращении к БД MSSql в виде таблицы.
 *  
 * @param stdClass $err_info Структура с ошибками
 * @return string Строка с ошибками
 */
function OutputErrors($err_info)
{
    global $OUTPUT;
    $strerror = '';
    $strerror .= 'Ошибки:<br><table align="center" width="100%" border="1">';
    $strerror .= '<tbody><tr><th>Тип ошибки</th><th>Сообщение</th></tr>';
    foreach( $err_info as $row) 
    {
        foreach( $row as $key => $value)
        {               
            if(preg_match('@[^\d]+@', $key))
            {
                $strerror .=  "<tr><td>".$key."</td><td>".$value."</td></tr>";
            }
        }
    }     
    $strerror .= '</tbody></table>';
    
    echo $OUTPUT->notification('Ошибка соединения с БД Деканат. Обратитесь к администратору СДО. Пожалуйста, сделайте снимок экрана с этой ошибкой и отправьте его в техподдержку.' . $strerror, 'error');
    echo $OUTPUT->footer();
    exit;
}
/**
 * Формирует запрос к бд и возвращает сруктуру с полями пользователя
 * 
 * @param string $userids
 * @param string $courseid
 * @param int_array $params
 * @return stdClass
 */
function GetUserRecordById($userids, $courseid, $params)
{
    global $DB;
    $sql1 = "SELECT  d.id AS recid
                , u.id
                , u.lastname
                , u.firstname
                , u.email
                , u.phone1
                , u.phone2
                , f.shortname 
                , d.data 
      FROM mdl_user u 
      LEFT join mdl_user_info_data as d ON
                d.userid = u.id
        LEFT join mdl_user_info_field as f ON
            f.id = d.fieldid  
        LEFT JOIN mdl_course_completions cc ON (u.id = cc.userid AND cc.course = $courseid) 
        WHERE u.id $userids ";
    $res = $DB->get_records_sql($sql1, $params);
    
    return $res; 
}
/**
 * Добавляет запись о зачислении студента на курс
 * 
 * @global stdClass $DB Переменная для работы с БД
 * @param int $cohortid ид учебной группы
 * @param int_array $userids Ид пользователей
 * @return stdClass Результат выполнения вставки
 */
function AddUserToCohort($cohortid, $userids)
{
    global $CFG, $DB;
    require_once("$CFG->dirroot/cohort/lib.php");
	try 
	{
		$transaction = $DB->start_delegated_transaction();
		foreach ($userids as $userid)
		{
			cohort_add_member($cohortid, $userid);
		}
		// Assuming the both inserts work, we get to the following line.
		$transaction->allow_commit();
	}
	 catch(Exception $e)
	{
		 $transaction->rollback($e);
	}
    
//        $members[] = array
//                    (
//                        'cohorttype'=>
//                            array
//                            (
//                                'type' => 'id',
//                                'value' => $cohortid
//                            ),
//                        'usertype' =>    
//                            array
//                            (
//                                'type' => 'id',
//                                'value' => $value
//                            )
//                    );
        
    
    //return core_cohort_external::add_cohort_members($members);
}
/**
 * Удаляет пользовател(я/ей) с укзазанн(ым/ыми) ид из группы с указанным ид
 * 
 * @global stdClass $DB
 * @param int $cohortid
 * @param array $userids
 */
function DeleteUserFromCohort($cohortid, $userids)
{
    global $CFG, $DB;
    require_once("$CFG->dirroot/cohort/lib.php");
	try 
	{
		$transaction = $DB->start_delegated_transaction();
		foreach ($userids as $userid)
		{
			cohort_remove_member($cohortid, $userid);
		}
		// Assuming the both inserts work, we get to the following line.
		$transaction->allow_commit();
	}
	 catch(Exception $e)
	{
		 $transaction->rollback($e);
	}
    
    
}
/**
 * Возвращает необходимые поля по заданному ид роли
 * 
 * @global stdClass $DB
 * @param int $roleid
 * @return stdClass
 */
function GetProfileRoles($roleid)
{
    global $DB;
    $sql = "SELECT DISTINCT r.id, r.name, r.shortname, r.sortorder, rn.name AS coursealias "
        . "FROM {role_assignments} ra, {role} r
         LEFT JOIN {role_names} rn ON rn.roleid = r.id 
         WHERE r.id = $roleid  
         ORDER BY r.sortorder ASC";
    return $DB->get_records_sql($sql);
}
/**
 * Возвращает набор групп для выпадающего списка
 * 
 * @global stdClass $DB DB API
 * @param string $cipher
 * @param bool $add_id
 * @return array
 */
function GetCohorts($cipher, $add_id = false)
{
    global $DB;
    $id_str = '';
    if($add_id)
        $id_str = ', c.idnumber';
    //Создаем запрос 
    $query = "  SELECT DISTINCT c.id, c.name $id_str
                FROM mdl_cohort c 
                WHERE  c.name LIKE '%$cipher%' ORDER BY c.name ASC ";
    if($add_id)
        return $DB->get_records_sql($query); 
    else
    {
        $temp = $DB->get_records_sql($query);
        $result = array();
        foreach($temp as $key => $val)
        {
            $result[$key] = $val->name;
        }
        return $result;
    }
}
/**
 * Возвращает ид итоговой оценки
 * 
 * @global stdClass $DB объект для работы с БД
 * @param mixed $course Структура с данными курса
 * @return int Ид итоговой оценки
 */
function GetTotalGradeId($course)
{
    global $DB;
    //берем ид итоговой оценки за курс
    $sql = " SELECT id FROM mdl_grade_items "
      . "WHERE courseid = $course->id AND itemtype = 'course'";
    return $DB->get_record_sql($sql);
}
/**
 * Выбирает заданное количество студентов с итоговыми оценками курса и даты завершения всех студентов на курсе. 
 * Также производит сортировку по дате или наибольшей оценки.
 * 
 * @global stdClass $DB
 * @param int $params ид контекста 
 * @param int $courseid ид курса 
 * @param int $roleid ид роли 
 * @param int $checkcount ограничение в количестве
 * @param int $sort параметр сортировки по ФИО
 * @param int $selectA параметр для выбора определенных студентов
 * @param int $TotalGradeid ид итоговой оценки
 * @param array $userids ид итоговой оценки
 * @return stdClass Массив структур с оценками и датами заврешения курса
 */
function GetFiltredGrades($params, $courseid, $roleid, $checkcount = 0, $sort = '0', $selectA = 0, $TotalGradeid = null, $userids = null )
{
    global $DB;
    
    //формируем запрос на получение студентов данного курса
    if(!isset($userids))
        $SubSql = get_course_users_sql_str($courseid, $roleid, $params);
    else
        $SubSql = implode(',', $userids);
    
    //опредляем столбцы выборки
    $select = "SELECT u.id, u.lastname AS lastname, u.firstname AS firstname, cc.timecompleted AS timecompleted, ";
        
    $select .= 'g.finalgrade AS grade '
                .  'FROM  mdl_user u ';
    $sortsql = '';
    //выбрали первых столько-то студентов
    if($selectA == 0)
    {
        $sortsql = "ORDER BY timecompleted ASC ";//по времени окончания по возрастанию
        //таблица с оценками не важна, ее просоединим слева
        $select .= "LEFT JOIN mdl_grade_grades g ON (g.itemid = $TotalGradeid AND g.userid = u.id)
                 JOIN mdl_course_completions cc  ON (u.id = cc.userid AND cc.course = $courseid AND cc.timecompleted IS NOT null)";
    }
    elseif($selectA == 1)
    {
        $sortsql = "ORDER BY grade DESC ";//по оценкам макс->мин
        //дата завершения не важна, присоединим ее слева
        $select .= "JOIN mdl_grade_grades g ON (g.itemid = $TotalGradeid AND g.userid = u.id AND g.finalgrade IS NOT NULL) 
                 LEFT JOIN mdl_course_completions cc  ON (u.id = cc.userid AND cc.course = $courseid ) ";
    }    
     //добавили сортировку, если надо
    if ($sort == '1')
        $sortsql .= ', lastname ASC, firstname ASC ';
    
    //запрос на получение таблицы с оценками по данным пользователям с установленными условиями
    $select .=  " WHERE u.id IN ( $SubSql ) "
        . " $sortsql";
    if($checkcount > 0)
       $select .= " LIMIT $checkcount" ;//если утановлено ограничение по количеству человек
    //debug_message('запросец:'.$DB->set_debug(true));
    $res = $DB->get_records_sql($select);
    //('конец.'.$DB->set_debug(false));
    return $res;
}
/**
 * Возвращает sql для выборки студентов (или любой другой роли) заданного курса
 * @param int $courseid ид курса
 * @param int $roleid ид роли
 * @param int $params ид контекста
 * @return string Строка SQL
 */
function get_course_users_sql_str($courseid, $roleid, $params)
{
    $sqlstr = "  SELECT DISTINCT eu1_u.id  
                FROM mdl_user eu1_u
                JOIN mdl_user_enrolments eu1_ue ON eu1_ue.userid = eu1_u.id
                JOIN mdl_enrol eu1_e ON ( eu1_e.id = eu1_ue.enrolid AND eu1_e.courseid = $courseid)
                WHERE eu1_u.id IN (SELECT userid FROM mdl_role_assignments WHERE roleid = $roleid AND contextid = $params) 
              ";
    return $sqlstr;
}
/**
 * Возвращает массив структур с ФИО, время-дата завершения курса, номер личного дела на данной программе в испк
 * @global stdClass $DB структура для работы с БД
 * @param int $params ид контекста
 * @param int $courseid ид курса
 * @param int $roleid ид роли
 * @param string $cipher шифр программы
 * @return stdClass массив структур
 */
function get_course_studentnumbers($params, $courseid, $roleid, $cipher)
{
    global $DB;
    //получли sql для выборки студентов данного курса
    $SubSql = get_course_users_sql_str($courseid, $roleid, $params);
    
    //опредляем столбцы выборки
    $select = "SELECT u.id, u.lastname AS lastname, u.firstname AS firstname,from_unixtime(cc.timecompleted,'%d.%m.%y %H:%i:%s') AS timecompleted, "; 
    $select .=  "stdn.privatnumber  AS privatnumber 
             FROM  mdl_user u 
             JOIN mdl_course_completions cc  ON (u.id = cc.userid AND cc.course = $courseid AND cc.timecompleted IS NOT null)
             LEFT JOIN mdl_pstgu_studentnumbers stdn ON (u.id = stdn.userid AND programmcipher = '$cipher')
             WHERE u.id IN ( $SubSql ) ORDER BY lastname ASC, firstname ASC";
   
    return $DB->get_records_sql($select);
}

/**
 * Возвращает массив структур с ФИО, время-дата завершения курса, номер личного дела на данной программе в испк
 * Вызывается для отметки галочками, чтобы не сбивалась сортировка
 * @global stdClass $DB структура для работы с БД
 * @param array $usedids ид пользователей, которых нужно отметить.
 * @param int $courseid ид курса
 * @param int $roleid ид роли
 * @param string $cipher шифр программы
 * @return stdClass массив структур
 */
function get_course_studentnumbers_check($usedids, $courseid, $roleid, $cipher)
{
    global $DB;
    //получли sql для выборки студентов данного курса
    $SubSql = implode(',', $usedids);
    
    //опредляем столбцы выборки
    $select = "SELECT u.id, u.lastname AS lastname, u.firstname AS firstname,from_unixtime(cc.timecompleted,'%d.%m.%y %H:%i:%s') AS timecompleted, "; 
    $select .=  "stdn.privatnumber  AS privatnumber 
             FROM  mdl_user u 
             JOIN mdl_course_completions cc  ON (u.id = cc.userid AND cc.course = $courseid AND cc.timecompleted IS NOT null)
             LEFT JOIN mdl_pstgu_studentnumbers stdn ON (u.id = stdn.userid AND programmcipher = '$cipher')
             WHERE u.id IN ( $SubSql ) ORDER BY lastname ASC, firstname ASC";
   
    return $DB->get_records_sql($select);
}

/**
 * Выводит сообщение в тегах перехода на новую строку.
 * @param string $message
 */
function debug_message($message)
{
    echo html_writer::start_tag('br') . $message. html_writer::end_tag('br');
}
/**
 * Сначала получаем фио пользователей, которые были выбраны по заданным критериям раньше, если нужно, сортируем их по ФИО
 * Потом получаем фио, оценки и даты завершения (если есть) остальных пользователей на заданном курсе. 
 * И все это объединяем в один массив структур.
 * 
 * @global stdClass $DB  Объект для работы с БД
 * @param int $sort Параметр сортировки по фио
 * @param array $usrids массив с ид пользователей
 * @param int $TotalGradeid ид итоговой иценки
 * @param int $courseid ид курса
 * @param int $roleid ид роли
 * @param int $params ид контекста
 * @return stdClass Массив структур с ФИО, а также датой завершнеия и итоговой оценкой, ести такие имеются
 */
function MergeUserList( $usrids, $TotalGradeid, $courseid, $roleid, $params, $cipher)
{
    global $DB, $CFG;
    
    $where = "";
    $usql = null;
    $params2 = null;
    if(count($usrids) !=0)
    {
        //подготовка запроса
        list($usql, $params2) = $DB->get_in_or_equal($usrids);
        $where = "WHERE u.id $usql ";
    }
    else
    {
        $where = 'WHERE u.id = -1';
    }    
    
    //теперь получаем студентов не прошедших условия
    //формируем запрос на получение оставшихся студентов данного курса, берем их оценки и заврешения, если они есть
    $SubSql = "  SELECT eu1_u.id, eu1_u.lastname AS lastname, eu1_u.firstname AS firstname, g.finalgrade AS grade, from_unixtime(cc.timecompleted,'%d.%m.%y %H:%i:%s') AS timecompleted, stdn.privatnumber AS privatnumber      
                    ,
                    (
                        SELECT CONCAT
                        (
                            mdl_course.shortname,
                            ' (',
                            (
                                SELECT IF (mdl_grade_grades.finalgrade IS NULL, '-',
                                CASE mdl_grade_items.gradetype
                                WHEN 0 THEN 'Не оценивается' 
                                WHEN 1 THEN -- значение
                                    CASE mdl_grade_items.display 
                                        WHEN 0 THEN -- если по умолчанию, тогда идем в mdl_config
                                                CASE (SELECT mdl_config.value FROM mdl_config WHERE mdl_config.name = 'grade_displaytype')
                                                    WHEN 1 THEN ROUND( mdl_grade_grades.finalgrade,2)
                                                    WHEN 2 THEN CONCAT( ROUND( mdl_grade_grades.finalgrade/mdl_grade_items.grademax*100, 2), '%')
                                                END
                                        WHEN 1 THEN ROUND( mdl_grade_grades.finalgrade, 2)
                                        WHEN 2 THEN CONCAT (ROUND( mdl_grade_grades.finalgrade/mdl_grade_items.grademax*100, 2), '%')
                                    END
                                WHEN 2 THEN -- шкала
                                    CASE ROUND( mdl_grade_grades.finalgrade, 0) 
                                        WHEN 2 THEN 'зачтено'
                                        WHEN 1 THEN 'не зачтено'
                                    END
                                END)
                                FROM mdl_grade_grades, mdl_grade_items 
                                WHERE mdl_grade_grades.userid = eu1_u.id
                                AND mdl_grade_items.courseid = mdl_course.id 
                                AND mdl_grade_items.itemtype = 'course' 
                                AND mdl_grade_grades.itemid = mdl_grade_items.id
                            ),
                            ') '
                        )   
                        FROM mdl_course 
                        WHERE mdl_course.shortname LIKE '$CFG->report_pstgu_deanery_OP%'
                        ORDER BY 
                        (
                            select from_unixtime(mdl_course_completions.timecompleted,'%d.%m.%y %H:%i:%s')
                            from mdl_course_completions
                            where mdl_course_completions.userid = eu1_u.id
                            and	mdl_course_completions.course = mdl_course.id
                        )
                        DESC LIMIT 1
                        ) AS op_completion,
                        (
                            SELECT CONCAT
                            (
                                mdl_course.shortname,
                                ' (',
                                (
                                    SELECT IF (mdl_grade_grades.finalgrade IS NULL, '-',
                                    CASE mdl_grade_items.gradetype
                                    WHEN 0 THEN 'Не оценивается' 
                                    WHEN 1 THEN -- значение
                                        CASE mdl_grade_items.display 
                                            WHEN 0 THEN -- если по умолчанию, тогда идем в mdl_config
                                                CASE (SELECT mdl_config.value FROM mdl_config WHERE mdl_config.name = 'grade_displaytype')
                                                        WHEN 1 THEN ROUND( mdl_grade_grades.finalgrade, 2)
                                                        WHEN 2 THEN CONCAT(ROUND( mdl_grade_grades.finalgrade/mdl_grade_items.grademax*100, 2), '%')
                                                END
                                            WHEN 1 THEN ROUND( mdl_grade_grades.finalgrade, 2)
                                            WHEN 2 THEN CONCAT(ROUND( mdl_grade_grades.finalgrade/mdl_grade_items.grademax*100, 2), '%')
                                        END
                                    WHEN 2 THEN -- шкала
                                        CASE ROUND( mdl_grade_grades.finalgrade, 0) 
                                                WHEN 2 THEN 'зачтено'
                                                WHEN 1 THEN 'не зачтено'
                                        END
                                    END)
                                    FROM mdl_grade_grades, mdl_grade_items 
                                    WHERE mdl_grade_grades.userid = eu1_u.id
                                    AND mdl_grade_items.courseid = mdl_course.id 
                                    AND mdl_grade_items.itemtype = 'course' 
                                    AND mdl_grade_grades.itemid = mdl_grade_items.id
                                ),
                                ') '
                            )   
                            FROM mdl_course 
                            WHERE mdl_course.shortname LIKE '$CFG->report_pstgu_deanery_OK%'
                            ORDER BY 
                            (
                                select from_unixtime(mdl_course_completions.timecompleted,'%d.%m.%y %H:%i:%s')
                                from mdl_course_completions
                                where mdl_course_completions.userid = eu1_u.id
                                and	mdl_course_completions.course = mdl_course.id
                            )
                            DESC LIMIT 1
                        ) AS ok_completion 
                FROM mdl_user eu1_u
                JOIN mdl_user_enrolments eu1_ue ON eu1_ue.userid = eu1_u.id
                JOIN mdl_enrol eu1_e ON ( eu1_e.id = eu1_ue.enrolid AND eu1_e.courseid = $courseid)
                LEFT JOIN mdl_grade_grades g ON (g.itemid = $TotalGradeid AND g.userid = eu1_u.id) 
                LEFT JOIN mdl_course_completions cc  ON (eu1_u.id = cc.userid AND cc.course = $courseid) 
                LEFT JOIN mdl_pstgu_studentnumbers stdn ON (eu1_u.id = stdn.userid AND programmcipher = '$cipher')
                WHERE eu1_u.id IN (SELECT userid FROM mdl_role_assignments WHERE roleid = $roleid AND contextid = $params) 
              ";
    if(is_null($usql))
        $usql = ' IN (-1)';
    if(strripos($usql,'=') !== false)
           $usql = '!' . $usql ;
    else
        $usql = 'NOT ' . $usql ;
    $SubSql .= " AND eu1_u.id $usql ORDER BY lastname ASC, firstname ASC";
    //debug_in_file($SubSql);
    return $DB->get_records_sql($SubSql, $params2);
}
/**
 * Записывает переданную строку  в файл
 * @param string $sql_string строка (например sql), которую надо записать
 * @param string $filename имя файла, по умолчанию sql_debug.sql
 */
function debug_in_file($sql_string, $filename = "sql_debug.sql")
{      
    $fd = fopen($filename, 'w') or die("Error of creating file");
    fwrite($fd, $sql_string);
    fclose($fd);
}
/**
 * Делает выборку наваний учебных групп по каждому из пользователей.
 * 
 * 
 * @global stdClass $DB Объект для работы с БД
 * @param array $userlist Массив с ид пользователей
 * @param string $cipher Фильтр для групп (шифр программы)
 * @return stdClass Массив с названиями учебных групп по каждому пользователю
 */
function GetUserCohort($userlist, $cipher = '')
{
    global $DB;
    //получили массив ид студентов для получения учебной группы каждого
    $usrids = array();
    //извлекаем ид пользователей
    foreach($userlist as $user)
        $usrids[] = $user->id;
    // формируем запрос
    if(count($usrids) !=0)
    {
        $params2 = null;
        //подготовка запроса
        list($usql, $params2) = $DB->get_in_or_equal($usrids);
        $where = "WHERE u.id  $usql ";
    }
    else
    {
        $where = 'WHERE cm.userid = -1';
    }
    // получаем по каждому пользователю список групп
    $cohortsql = "
        SELECT u.id,        
        (
            SELECT group_concat(c.name ORDER BY c.name ASC  SEPARATOR ' // ') 
            FROM mdl_cohort_members cm 
            JOIN mdl_cohort c ON cm.cohortid = c.id 
            WHERE cm.userid = u.id 
            AND c.name LIKE '%$cipher%'     
        ) AS name             
        FROM mdl_user u         
        $where  ";
		//debug_in_file(implode(',', $params2));
    return $DB->get_records_sql($cohortsql, $params2);
}
/**
 * Получает данные формы, выполняет выбранные на форме действия добавления, удаления, экспорта.
 * 
 * @global stdClass $DB Структура для работы с БД
 * @global stdClass $USER Структура с инфо текущего пользователя
 * @param stdClass $formdata Данные с формы
 * @return string Ответ процедуры или просто ОК
 */
function ExecuteAct($formdata, $actselect, &$info)
{
    global $DB, $USER;
    $courseid = $formdata->id;
    $users =  $formdata->users; 
    $cohortid = null;
    
    $course = $DB->get_record('course', array('id'=>$courseid));
    // для ответа
    $xmlOut = '';
    //извлекаем ид юзеров
    $keys = array_keys($users);
    $userids = '';
    //делаем предложение IN (?,? ....)
    list($userids, $params) = $DB->get_in_or_equal($keys);
    
    $cipherProg = '';
    if($actselect == '3' || $actselect == '4')
    {
        $cohortid = $formdata->cohortmenu;
        $cipherProg = $DB->get_record('cohort', array('id' => $cohortid));
    }
    else
    {
        $cipherProg = $course->shortname;
    }    
    if(!is_null($cohortid) && ($actselect == '3' || $actselect == '4'))
    {
        $cipherProg = $DB->get_record('cohort', array('id' => $cohortid));
        if($actselect == '3')
        {
            AddUserToCohort($cohortid, $params);
        }
        elseif($actselect == '4')
        {
            DeleteUserFromCohort($cohortid, $params);
        }
        $xmlOut = 'OK';
    }
    else
    {
        $Action = '';
        if($actselect == '0')
            $Action = 'СоздатьАбитуриентовИзаявления';//'add';
        elseif($actselect == '1')
            $Action = 'УдалитьАбитуриентовИзаявления';//'delete';
        elseif($actselect == '2')
            $Action = 'ОбновитьДанныеАбитуриентов';//'update';
        //получили инфо о том, кто произвел данный экспорт
        $creator =  $DB->get_record('user', array('id' => $USER->id));
        //получили структуру с полями пользователя
        $res = GetUserRecordById($userids, $courseid, $params);
        //получили строку для дальнейшего запроса
        $xmlData = GetXML($res, $creator, $cipherProg, $Action); 
        //Получили ответ от сервера 
        $xmlOut = ConnectMSSql($xmlData);
        // запись личных дел
        if(strpos($xmlOut, 'ODBC') === FALSE)
        {
            $xmlOut = InsertXML($xmlOut, $cipherProg, $info);
        }
    }
    //освободил переменную
    unset($res);
    return $xmlOut;
}
/**
 * Собирает строку с ФИО пользователей, ид которых передаются в качестве параметров
 * 
 * @global stdClass $DB Структура для работы с БД
 * @param array $userids Ид пользователей
 * @return string Имя и фамилии, соединенные пробелом, в теге абзаца
 */
function GetStringUserName($userids)
{
    global $DB;
    list($usql, $params) = $DB->get_in_or_equal($userids);
    $sql = "SELECT u.id, u.firstname, u.lastname FROM mdl_user u WHERE u.id $usql";
    $res = $DB->get_records_sql($sql, $params);
    $names = '';
    foreach($res as $value)
    {
        $names .= '<p>' . $value->firstname . ' ' . $value->lastname . '</p> ';
    }
    $names = substr($names, 0, strlen($names) - 2);
    return $names;
}
/**
 * Получает хмл строку, извлекает результаты записи\добавления редактироваия личных дел в испк
 * @global stdClass $DB для работы с бд
 * @param xmlstring $xmlStr хмл строка с данными
 * @param string $programmcipher шифр прогаммы
 * @return boolean
 */
function InsertXML($xmlStr, $programmcipher, &$info)
{
    global $DB;
    
    $xmlREADER = new XMLReader();
    $xmlREADER->xml($xmlStr);//Загрузка  строки в формате XML
    
    //устанавливаем языковые настройки локали для парсинга
    setlocale(LC_ALL, 'ru_RU.utf8');
    //разделили на массив из строк
    $arr = preg_split('/[^\w]+/u', $programmcipher);
    if($arr[0] == "ПК" || $arr[0] == "ОК")
    {
        // удалили лишние пробелы
        $cipher = preg_replace("/\s{2,}/",' ',$arr[1]);
        $year = preg_replace("/\s{2,}/",' ',$arr[2]);
    }
    else
    {
        // удалили лишние пробелы
        $cipher = preg_replace("/\s{2,}/",' ',$arr[0]);
        $year = preg_replace("/\s{2,}/",' ',$arr[1]);
    }
    
    $fd = fopen("XMLdebugOUT.xml", 'w') or die("Error of creating file");
    fwrite($fd, $xmlStr);
    fclose($fd);
    $flag = FALSE;        
    $IstudentsSQL = '';
    $Istudents = array();
    $DstudentsSQL = array();
    $notfound = array();
    $update = array();
    $delete = array();
    $error = '';
    while($xmlREADER->read())
    {
        if($xmlREADER->nodeType == XMLReader::ELEMENT)
        {
            if($xmlREADER->localName == 'Абитуриент' /*'Student'*/)
            {    
                //получаем все теги узла 'Student'
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                $id = $node->attributes();
                switch ($node->РезультатЗаявление)
                {
                    case 'Add':                        
                        if(!$flag)
                        {
                            $IstudentsSQL = "SELECT $id as userid,";  
                            $IstudentsSQL .= " '$node->Фамилия $node->Имя $node->Отчество' as fio,"; 
                            $IstudentsSQL .= " '$cipher' as programmcipher,"; 
                            $IstudentsSQL .= " '$node->НомерЗаявления' as privatnumber,";
                            $IstudentsSQL .= " '$year' as year \r\n";
                            $Istudents[] = "$node->Фамилия $node->Имя $node->Отчество";
                        }
                        else
                        {
                            $IstudentsSQL .= "UNION SELECT $id,";
                            $IstudentsSQL .= " '$node->Фамилия $node->Имя $node->Отчество',";
                            $IstudentsSQL .= " '$cipher',";
                            $IstudentsSQL .= " '$node->НомерЗаявления',";
                            $IstudentsSQL .= " '$year' \r\n";
                            $Istudents[] = "$node->Фамилия $node->Имя $node->Отчество";
                        }
                        $flag = TRUE;
                        $info[$id.''] = 'Дело заведено.';
                    break;
                    case 'delete':
                        $DstudentsSQL[] = "'$node->НомерЗаявления'";
                        $delete[] = "$node->Фамилия $node->Имя $node->Отчество";
                        $info[$id.''] = 'Дело удалено.';
                    break;
                    case 'not found':
                        $notfound[] = "$node->Фамилия $node->Имя $node->Отчество";
                        $info[$id.''] = 'Дело не найдено.';
                    break;
                    case 'Found, not updated': 
                    case 'update':
                        $update[] = "$node->Фамилия $node->Имя $node->Отчество";
                        $info[$id.''] = 'Дело обновлено.';
                    break;
                }
            }
            if($xmlREADER->localName == 'Error')
            {
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                $error = "Ошибки при выполнении процедуры: $node";
            }
        }
    }

    $sqlInsert = "
        INSERT INTO mdl_pstgu_studentnumbers (userid, fio, programmcipher, privatnumber, year)
        SELECT * FROM 
        (	
            $IstudentsSQL 
        ) as t1
        WHERE NOT EXISTS 
        (
                SELECT * FROM mdl_pstgu_studentnumbers as t2
                WHERE
                        t1.userid = t2.userid
                        AND t1.programmcipher = t2.programmcipher
                        AND t1.privatnumber = t2.privatnumber
                        AND t1.year = t2.year
        ) ";
    $sqlDelete = 'DELETE FROM `mdl_pstgu_studentnumbers` WHERE `privatnumber` IN ('.implode(',', $DstudentsSQL).')';
//    echo 'Сформировали строку запроса:<br><pre>';
//    echo "$sqlInsert</pre>";
    $result = '';
    if(count($Istudents) != 0)
    {
        $DB->execute($sqlInsert);
        $result .= "<p>Абитуриенты добавлены, заявления созданы: <br>" . implode("<br>", $Istudents).'</p>';
    }
    if(count($update) != 0 )
    {
        $result .= "<p>Профиль абитуриентов обновлен в ИС ПК: <br>". implode("<br>", $update).'</p>';
    }
    if(count($DstudentsSQL) != 0)
    {
        $DB->execute($sqlDelete);
        $result .= "<p>Удалены заявления и абитуриенты: <br>". implode("<br>", $delete).'</p>';
    }    
    if(count($notfound) != 0 )
    {
        $result .= "<p>Абитуриенты не найдены: <br>". implode("<br>", $notfound).'</p>';
    }
    
    if($error != '')
        return $error;
    return $result;    
}
/**
 * Делает тоже самое, что и GetYear, только из строки, которая сохранилавсь в файле.
 * Это временное решение, тк. на локальном сервере стоит php5.6 вместе с ODBC 11, который не может обрабатывать большую строку. 
 * @param array $years
 */
function GetYearString(&$years)
{
    $xmlREADER = new XMLReader();
    $xmlREADER->open('XMLdebugOUTGroup.xml');
    while($xmlREADER->read())
    {
        if($xmlREADER->nodeType == XMLReader::ELEMENT)
        {
            if($xmlREADER->localName == 'УчебныйГод')
            {
                //получаем все теги узла 'Student'
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                $years[] = $node->attributes();                
            }
        }        
    }
}
/**
 * Делает соответствие для каждой группы из СДО группой из меню с группами ИС Деканат 
 * @param type $selecеisyear выбранный год с учетом текущего месяца, например 2016 или 2017, не 2016-2017!!!
 * @param type $selectsemestr Выбранный семестр из меню семестр
 * @param type $result Массив с группами СДО
 * @param type $groupdeaneryoptions Массив с группами ИС. Каждой группе СДО соответствует набор подходящих групп из ИС
 * @param type $selectionsyear Массив соответствия.
 * @return string Строка результата поиска соответствий
 */
function FindMatch($selecеisyear, $selectsemestr, $cohorts, $groupdeaneryoptions, &$selectionsyear)
{
    foreach($cohorts as $cohort)
    {
        $arr = preg_split('/[^\w]+/u', $cohort->name);
        
        foreach($groupdeaneryoptions[$arr[2]] as $key => $val)
        {
            $gr = preg_split('/[^\w]+/u', $val);
            if((int)date('m') < 9)
                $gry = $gr[3];
            else
                $gry = $gr[4];
            $cipherSDO = $arr[0];
            $cipherIS = $gr[0];
            $semestrIS =$gr[2];
            if($cipherSDO == $cipherIS && $gry == $selecеisyear && ($semestrIS == $selectsemestr || '0'.$semestrIS == $selectsemestr))
            {
                $selectionsyear[$cohort->name] = $key;
            }
        }
    }    
    //если не для всех групп найдено совпадение
    if(count($selectionsyear) < count($cohorts)) 
        return'Worning';  
    
    return 'OK';
}
/**
 * Производит подключение к MsSQL Sever и выполняет вызов хранимой процедуры,
 * которая получет шифр программы и возвращает хмл структуру с годами и группами.
 * @param string $cipher Строка с шифром программы
 */
function GetYearGr($cipher)
{
    $xmlData = '<Запрос Тип="ВсеГруппыОткрытыхУчебныхГодов">
                    <ШифрПрограммы>'.$cipher.'</ШифрПрограммы>
                </Запрос>';
    
    $conn = edvp_connect('A');
    // для отправки ошибок
    $strerror = '';
       
    $tsql_callSP = "{call sp_PSTGU_IDO_SyncGroups( ?, ?)}";  
    $xmlOut = "";
    $params = array(   
                     array($xmlData, SQLSRV_PARAM_IN),  
                     array(&$xmlOut, SQLSRV_PARAM_INOUT)  
                   );
    /* Execute the query. */  
    $stmt = sqlsrv_query( $conn, $tsql_callSP, $params); 
    // в случае ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors();
        sqlsrv_close( $conn);
        OutputErrors($err_info);        
    }
    sqlsrv_next_result($stmt);
    if(is_null($xmlOut))
    {
        return 'null';
    }
    $xmlREADER = new XMLReader();
    $xmlREADER->XML($xmlOut);
    $xmlREADER->read();
    $id = $xmlREADER->readString();
    // Строка селекта [Абитуриенты_Т].dbo.
    $tsql_callSP = "SELECT [Output_XML]
                    FROM v_PSTGU_CDO_ImportResult
                    WHERE [id] = ?";   
    $params = array(   
                    array($id, SQLSRV_PARAM_IN),                      
                    );
    //производим запрос
    $stmt = sqlsrv_query( $conn, $tsql_callSP, $params); 
    //случай ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors();
        sqlsrv_close( $conn);
        OutputErrors($err_info);       
    }
    $xmlOut = '';
    while( $row = sqlsrv_fetch_object( $stmt ))
    {
        $xmlOut = $row->Output_XML;
    }
    /*Free the statement and connection resources. */  
    sqlsrv_free_stmt( $stmt);  
    sqlsrv_close( $conn);   
    $fd = fopen("XMLdebugOUTGroup.xml", 'w') or die("Error of creating file");
    fwrite($fd, $xmlOut);
    fclose($fd);    
    return $xmlOut;   
}
/**
 * Извлекает года для выпадающего списка из хмл строки
 * @param array $years Массив для годов по ссылке
 * @param string $cipher строка с шифром
 */
function GetYear(&$years, $cipher )
{
    global $OUTPUT;
    $conn = edvp_connect();    
    // Строка селекта 
    $tsql_select = "SELECT DISTINCT УчебныйГод
                    FROM ПСТГУ_ИДО_Все_Группы 
                    WHERE Шифр = ?";   
    $params = array(   
                    array($cipher, SQLSRV_PARAM_IN),                      
                    );
    //производим запрос
    $stmt = sqlsrv_query( $conn, $tsql_select, $params); 
    //случай ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors();
        sqlsrv_close( $conn);
        OutputErrors($err_info);       
    }
    /* Retrieve each row as  php objects and display the results */  
    while( $row = sqlsrv_fetch_object( $stmt ))
    {  
        $k= 0;    
        $years[$row->УчебныйГод] = $row->УчебныйГод;
    }
    if( $row === false)  
    {   
        $err_info = sqlsrv_errors();
        sqlsrv_free_stmt( $stmt);  
        sqlsrv_close( $conn);
        OutputErrors($err_info);
    }
    if(count($years) == 0)
    {
        echo $OUTPUT->notification('Открытых учебных годов в ИС Деканат не найдено. Обратитесь к администратору СДО.','error');
        echo $OUTPUT->footer();
        exit;
    }
}
/**
 * Извлекает группы из хмл строки
 * @param array $Groups массив для групп по ссылке
 * @param string $StrokaOut строка с хмл структурой
 */
function GetGroups(&$Groups, $StrokaOut, $cohorts)
{
    $xmlREADER = new XMLReader();
    $xmlREADER->xml($StrokaOut);//Загрузка  строки в формате XML
    foreach($cohorts as $cohort)
    {
        $arr = preg_split('/[^\w]+/u', $cohort);
        $groupdeaneryoptions[$arr[2]] = '';
    }
    //получили текущий год
    $Ty = date("Y");
    while($xmlREADER->read())
    {
        if($xmlREADER->nodeType == XMLReader::ELEMENT)
        {
            if($xmlREADER->localName == 'УчебныйГод')
            {
                //получаем все теги узла 'Student'
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                $arr = preg_split('/[^\w]+/u', $node->attributes());                
                if($arr[1] == $Ty)
                    foreach($node->Группа as $val)
                    {      
                        foreach($groupdeaneryoptions as $key => $value)
                        {
                            if(($arr[2] == $selectsemestr.'' || $arr[2] == '0'.$selectsemestr ) && $key == $arr[1])
                            {
                                //$k=0;                            
                                $groupdeaneryoptions[$key] = $val->attributes();
                            }
                        } 
                        $Groups[] =  $val->attributes() ;
                    }
            }
        }        
    }
}
/**
 * Возвращает индекс текущего года
 * @param array $optionsDean массив с годами
 * @return int индекс
 */
function GetTempYearIndex($optionsDean)
{
    foreach($optionsDean as $y)
    {
        if(strpos($y.'', date("Y")) !== false)
        {
            if((int)date('m') < 9) 
                return array_search($y , $optionsDean);
            else
                return array_search($y , $optionsDean)+1;
        }
    }
}
function GetYearSDO($result, &$options)
{
    $options = array();
    foreach($result as $value)
    {
        //поделили по словам 
        $arr = preg_split('/[^\w]+/u', $value);
        if(!in_array($arr[1], $options))
            //записали только год
            $options[] = $arr[1];
    }
}
/**
 * Производит подключение к edvp серверу
 * @return ref Ссылка с поключением к edvp
 */
function edvp_connect($db = 'D')
{   
    //получили имя домена
    $selfname = $_SERVER['SERVER_NAME'];
    switch($selfname)
    {
        case 'localhost':
            $serverName =  '10.0.1.12';//по локальной сети
            break;
        default :
            $serverName = '195.91.136.74,8328';//с рабочего сервера сдо
    }
    // сервер на localhost $serverName = 'QM-083N\SQLEXPRESS';
    //берем необходимые параметры для соединения из файла конфигурации
//    $filename = "connection_settings.ini";    
//    if (!$conf = parse_ini_file($filename))
//    {
//        global $OUTPUT;
//        echo $OUTPUT->notification("Не удалось открыть файл: $filename", 'error');
//        echo $OUTPUT->footer();
//        exit;
//    }
    $db_tmp = '';
    switch($db)
    {
        case 'A':
            $db_tmp = get_config('core','report_pstgu_deaneryAb'); //$conf['DB_abit'];
        break;
        default:
            $db_tmp = get_config('core','report_pstgu_deaneryDec');//$conf['DB_dean'];
    }
    $connectionOptions = array(
                                "Database"=>               $db_tmp, 
                                "UID"=>                    'idosdo',//$conf['UID'], 
                                "PWD"=>                    'tMPtkP0NO',//$conf['PWD'], 
                                "Encrypt"=>                "true", 
                                "TrustServerCertificate"=> "false", 
                                "CharacterSet" =>          "UTF-8"
                            );
    
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if( $conn === false ) 
    {
        $err_info = sqlsrv_errors();
        OutputErrors($err_info); 
    } 
    return $conn;
}
/**
 * 
 * @param stdClass $formdata
 */
function SaveRelations($formdata, $result)
{
    global $DB;
    $fr = json_decode(json_encode($formdata), true);
    $relations = $fr['groupdeanery'];
    $WHEN = '';
    
    foreach($result as $key => $value)
    {
        $group = $relations[$value->name];        
        $WHEN .= "WHEN id = $key THEN '$group'\r\n";
        
    }
    $WHEN .= ' ELSE idnumber END ';
    
    $sqlUPDATE = "UPDATE mdl_cohort SET idnumber = CASE
                    $WHEN    ";
    $DB->execute($sqlUPDATE);
    return true;
}

function GetResExport($info)
{
    $xmlREADER = new XMLReader("XMLdebugOUT.xml");    
    while($xmlREADER->read())
    {
        if($xmlREADER->nodeType == XMLReader::ELEMENT)
        {
            if($xmlREADER->localName == 'Абитуриент' /*'Student'*/)
            {    
                //получаем все теги узла 'Student'
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                switch ($node->РезультатЗаявление)
                {
                    
                    
                        
                }
            }
            if($xmlREADER->localName == 'Error')
            {
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                $error = "Ошибки при выполнении процедуры: $node";
            }
        }
    }
    
}

/**
 * Проводит проверку на наличие годов без учебных групп
 * 
 * @param string $xml структура с xml
 */
function IsNullGroups($xml)
{
    $xmlREADER = new XMLReader();
    $xmlREADER->xml($xml);//Загрузка  строки в формате XML  
    $strWar = '';
    while($xmlREADER->read())
    {   
        if($xmlREADER->nodeType == XMLReader::ELEMENT)
        {
            if($xmlREADER->localName == 'УчебныйГод')
            {
                //получаем содержимое тегов
                $node = new SimpleXMLElement($xmlREADER->readOuterXML());
                $year = $node->attributes() . '';
                if(count($node->Группа) == 0)
                {
                    $strWar .= "<p>$year</p>";                    
                }                
            }
        }        
    }
    return $strWar;
}
/**
 * Формирует массив по количеству учебных групп СДО
 * Для каждого элемента массива создается массив из подходящих под условие групп из ИС Деканат
 * @param type $cohorts учебные группы из СДО
 * @param type $groupdeaneryoptions пустой массив-заготовка
 * @param type $cipher шифр программы
 */
function GetGropMenu($cohorts, &$groupdeaneryoptions, $cipher)
{
    foreach($cohorts as $cohort)
    {
        $arr = preg_split('/[^\w]+/u', $cohort->name);
        $groupdeaneryoptions[$arr[2]] = array();
    }
    
    $conn = edvp_connect();
    
    // Строка селекта 
    $tsql_select = "SELECT Код, Группа, Шифр, УчебныйГод
                    FROM ПСТГУ_ИДО_Все_Группы 
                    WHERE Шифр = ?";   
    $params = array(   
                    array($cipher, SQLSRV_PARAM_IN),                      
                    );
    //производим запрос
    $stmt = sqlsrv_query( $conn, $tsql_select, $params); 
    //случай ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors();
        sqlsrv_close( $conn);
        OutputErrors($err_info);       
    }
    // записываем построчно результат
    while( $row = sqlsrv_fetch_object( $stmt ))
    {  
        //$k = 0;    
        $arr = preg_split('/[^\w]+/u', $row->Группа);//распарсили на слова
        if(isset($groupdeaneryoptions[$arr[1]])) 
        {
            $name = $row->Группа.' (' . $row->УчебныйГод . ')';
            $key = $row->Код .' '. $name;
            $groupdeaneryoptions[$arr[1]][$key] =  $name;
        }
    }
    
    if( $row === false)  
    {   
        $err_info = sqlsrv_errors();
        sqlsrv_free_stmt( $stmt);  
        sqlsrv_close( $conn);
        OutputErrors($err_info);
    }        
}
/**
 * Выводит тематические вкладки для действий в деканате
 * 
 * @global stdClass $CFG структура с настройкми сайта
 * @param int $tmp_tab номер текущей вкладки
 * @param int $courseid ид курса
 */
function print_deanery_tabs($tmp_tab, $courseid)
{
    global $CFG, $USER,$DB;
    $context = context_course::instance($courseid, MUST_EXIST);    
    //программный апи для вывода в виде вкладок
    $row = $tabs = array();
    //$DB->set_debug(true);
    //берем настройки по вкладкам
    $tab = $DB->get_records_sql("SELECT name, value FROM {config} WHERE name LIKE 'export_ispk%'"); 
    for($i = 1; $i < 6; $i++)
    {
        if($tab["export_ispk$i"]->value == '1')
        {
            $url = new moodle_url('/report/report_pstgu_deanery/index.php', array('id' => $courseid,'page' => $i));
            $row[] = new tabobject($i, $url, get_string('tab'.$i.'name', 'report_report_pstgu_deanery'));
        }
    }
    $tabs[] = $row;//добавили первый ряд вкладок
    print_tabs($tabs, $tmp_tab);
}
/**
 * Извлекает шифр курса и его год по алгоритму
 * 
 * @param array $arr массив строк, после парсинга шифра курса
 * @return stdClass
 */
function extract_course_cipher($arr)
{
    $course_ciph = (object) array('cipher' => '', 'year' => '');
    if($arr[0] == "ПК" || $arr[0] == "ОК")
    {
        // удалили лишние пробелы
        $course_ciph->cipher = preg_replace("/\s{2,}/",' ',$arr[1]);
        $course_ciph->year = preg_replace("/\s{2,}/",' ',$arr[2]);
    }
    else
    {
        // удалили лишние пробелы
        $course_ciph->cipher = preg_replace("/\s{2,}/",' ',$arr[0]);
        $course_ciph->year = preg_replace("/\s{2,}/",' ',$arr[1]);
    }
    return $course_ciph;
}

function get_course_enroll_users($courseid, $roleid, $params)
{
    global $DB;
    //формируем запрос на получение оставшихся студентов данного курса, берем их оценки и заврешения, если они есть
    $sql = "  SELECT DISTINCT eu1_u.id, eu1_u.lastname AS lastname, eu1_u.firstname AS firstname, d.data AS idisd      
                FROM mdl_user eu1_u
                JOIN mdl_user_enrolments eu1_ue ON eu1_ue.userid = eu1_u.id
                JOIN mdl_enrol eu1_e ON ( eu1_e.id = eu1_ue.enrolid AND eu1_e.courseid = $courseid)
                LEFT JOIN mdl_user_info_field f ON ( f.shortname = 'idisd')                 
                LEFT JOIN mdl_user_info_data d ON (d.fieldid = f.id AND eu1_u.id = d.userid)
                WHERE eu1_u.id IN (SELECT userid FROM mdl_role_assignments WHERE roleid = $roleid AND contextid = $params)
                ORDER BY lastname ASC, firstname ASC
              ";
    return $DB->get_records_sql($sql);
}

function get_users_by_cohort($courseid, $roleid, $params, $TotalGradeid, $cohortid)
{
    global $DB;
       
    //формируем запрос на получение оставшихся студентов данного курса, берем их оценки и заврешения, если они есть
    $sql = "SELECT eu1_u.id, eu1_u.lastname AS lastname, eu1_u.firstname AS firstname,
            from_unixtime(g.timemodified,'%d.%m.%y %H:%i:%s') AS gradetime, g.finalgrade AS grade     
                FROM mdl_user eu1_u
                JOIN mdl_user_enrolments eu1_ue ON eu1_ue.userid = eu1_u.id
                JOIN mdl_enrol eu1_e ON ( eu1_e.id = eu1_ue.enrolid AND eu1_e.courseid = $courseid)
                LEFT JOIN mdl_grade_grades g ON (g.itemid = $TotalGradeid AND g.userid = eu1_u.id)
                JOIN mdl_cohort_members cohm ON (eu1_u.id = cohm.userid AND cohm.cohortid = $cohortid)
                WHERE eu1_u.id IN (SELECT userid FROM mdl_role_assignments WHERE roleid = $roleid AND contextid = $params)
                ORDER BY lastname ASC, firstname ASC
              ";
    //debug_in_file($sql);
    return $DB->get_records_sql($sql);
}

function get_users_cohorts($usrids, $add_id = false)
{
    global $DB;
    
    $id_str = '';
    if($add_id)
        $id_str = ', c.idnumber';
    
    $sql = "SELECT DISTINCT c.name $id_str
            FROM mdl_cohort_members cm 
            JOIN mdl_cohort c ON cm.cohortid = c.id ";
    $usql = '';
    $params = array();
    //подготовка запроса
    list($usql, $params) = $DB->get_in_or_equal($usrids);
    $sql .= "WHERE cm.userid $usql ORDER BY c.name";
    
    $temp = $DB->get_records_sql($sql, $params);
    if(!$add_id)
    {
        $result = array();
        foreach($temp as $key => $val)
        {
            $result[$key] = $val->name;
        }
        return $result;
    }
    return  $temp;
}

function get_user_relations(&$user_from_is_dean, $group_key)
{
    if(!isset($group_key) || $group_key == '')
        return 'Для данной группы не было установлено соответствие.';

    $conn = edvp_connect();
    // для отправки ошибок
    $strerror = '';    
    // Строка селекта 
    $tsql_callSP = "SELECT Код, Фамилия, Имя, Отчество, Код_Группы
                    FROM ПСТГУ_ИДО_Все_Студенты
                    WHERE Код_Группы = ?";   
    $params = array(   
                    array($group_key, SQLSRV_PARAM_IN),                      
                    );
    //производим запрос
    $stmt = sqlsrv_query( $conn, $tsql_callSP, $params); 
    //случай ошибок
    if( $stmt === false )  
    {  
        $err_info = sqlsrv_errors();
        sqlsrv_close( $conn);
        OutputErrors($err_info);       
    }
      
    /* Retrieve each row as  php objects and display the results */  
    while( $row = sqlsrv_fetch_object( $stmt ))
    {  
        $k= 0;    
        $user_from_is_dean[$row->Код] = $row;  
    }
    
    if( $row === false)  
    {   
        $err_info = sqlsrv_errors();
        sqlsrv_free_stmt( $stmt);  
        sqlsrv_close( $conn);
        OutputErrors($err_info);
    }
    /*Free the statement and connection resources. */  
    sqlsrv_free_stmt( $stmt);  
    sqlsrv_close( $conn);       
    return 'OK';
}

function idisd_by_name($user, $userlist)
{
    $k =0;
    if(isset($user->Код))
    {
        $tmp = $user->Фамилия.' '.$user->Имя.' '.$user->Отчество;
        foreach($userlist as $u)
        {
            $fullname = $u->lastname.' '.$u->firstname;
            if( $fullname == $tmp)
            {
                return true;
            }
        }
        return false;
    }
    else
    {
        $tmp = $user->lastname.' '.$user->firstname;
        foreach($userlist as $u)
        {
            $fullname = $u->Фамилия.' '.$u->Имя.' '.$u->Отчество;
            if( $fullname == $tmp)
            {
                return $u->Код;
            }
        }
        return false;
    }
}

function set_user_relations($user_from_is_dean, $userlist, $cohortselection)
{
    global $DB;
  
    $WHEN = '';
    setlocale(LC_ALL, 'ru_RU.utf8');
    //разделили на массив из строк
    $cipher = preg_split('/[^\w]+/u', $cohortselection);
    foreach($userlist as $user)
    {
        $idisd = idisd_by_name($user, $user_from_is_dean);
        if($idisd !== false )
        {
            $WHEN .= "WHEN userid = $user->id THEN $idisd \r\n";
        }
                
    }
    if($WHEN != '')
        $WHEN .= ' ELSE idisd END ';
    $sqlUPDATE = "
        UPDATE mdl_pstgu_studentnumbers  SET  idisd = CASE
        $WHEN
        WHERE programmcipher = '$cipher[0]' AND year = '$cipher[1]'";
    
    if($WHEN != '')
        $DB->execute($sqlUPDATE);
    return true;
}

function get_group_key($idnumber)
{
    $arr = preg_split('/[^\w]+/u', $idnumber);
    // скопировали строку, выкинув из нее первую часть, содержащую ид группы в деканате
    $group = array();
    $group[] = $arr[0];
    $group[] = substr($idnumber, strlen($arr[0]), strlen($idnumber));
    return $group;
}


function page_develop_sorry_message()
{
    global $OUTPUT;
    echo $OUTPUT->notification(html_writer::tag('p', 'Страница в стадии разработки.') , 'warning');
    echo $OUTPUT->footer();            
    exit;
}


function load_note($courseid, $userids)
{
    global $DB;
    $sql = "SELECT p.userid, 
            (
                    SELECT COUNT(p1.id)
                FROM mdl_post p1
                    WHERE p1.publishstate = 'public' AND p1.courseid = $courseid AND p1.userid = p.userid
            ) AS cnt
            FROM mdl_post p
            WHERE p.publishstate = 'public' AND p.courseid = $courseid AND p.userid  ";
    $usql = '';
    $params = array();
    //подготовка запроса
    list($usql, $params) = $DB->get_in_or_equal($userids);
    $sql .= $usql;
    //echo $DB->set_debug(true);     
    return $DB->get_records_sql($sql, $params);
}

function save_note($content, $ids, $courseid)
{
    global $DB,$USER;
    $arr = preg_split("([^0-9])",$ids);
    // Setup & clean fields.
    $note = new stdClass();
    $note->id = $arr[0];
    $note->userid = $arr[1];
    $note->content = $content;
    $note->courseid = $courseid;
    $note->module       = 'notes';
    $note->lastmodified = time();
    $note->usermodified = $USER->id;
    if (empty($note->format)) 
    {
        $note->format = '2';
    }
    if (empty($note->publishstate)) 
    {
        $note->publishstate = 'public';
    }
    // Save data.
    if ($note->id == '0') 
    {
        // Insert new note.
        $note->created = $note->lastmodified;
        $DB->insert_record('post', $note);
    } 
    else 
    {
        // Update old note.
        $DB->update_record('post', $note);
    }
}
