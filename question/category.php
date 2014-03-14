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
 * This script allows a teacher to create, edit and delete question categories.
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../config.php");
require_once($CFG->dirroot."/question/editlib.php");
require_once($CFG->dirroot."/question/category_class.php");

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
        question_edit_setup('categories', '/question/category.php');

// Get values from form for actions on this page.
$param = new stdClass();
$param->delete = optional_param('delete', 0, PARAM_INT);
$param->confirm = optional_param('confirm', 0, PARAM_INT);
$param->edit = optional_param('edit', 0, PARAM_INT);

$param->move = optional_param('move', 0, PARAM_INT);            // Старт перемещения категории с указанным id
$param->id = optional_param('id', 0, PARAM_INT);      // Контекст перемещаемой категории
$param->moveto = optional_param('moveto', 0, PARAM_INT);        // Поместить над какой-то другой категории с указанным id
$param->movetocontext = optional_param('movetocontext', 0, PARAM_INT);  // Поместить в конец списка категорий в контексте или в пустой контекст с указанным id
$param->movein = optional_param('movein', 0, PARAM_INT);        // Поместить в подкатегорию категории с указанным id (под категорией)

$url = new moodle_url($thispageurl);
foreach ((array)$param as $key=>$value) {
    if (($key !== 'cancel' && $value !== 0) || ($key === 'cancel' && $value !== '')) {
        $url->param($key, $value);
    }
}
$PAGE->set_url($url);

$qcobject = new question_category_object($pagevars['cpage'], $thispageurl,
        $contexts->having_one_edit_tab_cap('categories'), $param->edit,
        $pagevars['cat'], $param->delete, $contexts->having_cap('moodle/question:add'));

$qcobject->try_move_start($param->move);  // Если начали перемещать категорию.
$qcobject->try_move_finish($param->id, $param->movetocontext, $param->moveto, $param->movein);
$qcobject->try_move_cancel($param->id, $param->movetocontext, $param->moveto, $param->movein);             // Отменили перемещение категории.

if ($param->delete && ($questionstomove = $DB->count_records("question", array("category" => $param->delete)))) {
    if (!$category = $DB->get_record("question_categories", array("id" => $param->delete))) {  // security
        print_error('nocate', 'question', $thispageurl->out(), $param->delete);
    }
    $categorycontext = context::instance_by_id($category->contextid);
    $qcobject->moveform = new question_move_form($thispageurl,
                array('contexts'=>array($categorycontext), 'currentcat'=>$param->delete));
    if ($qcobject->moveform->is_cancelled()){
        redirect($thispageurl);
    }  elseif ($formdata = $qcobject->moveform->get_data()) {
        /// 'confirm' is the category to move existing questions to
        list($tocategoryid, $tocontextid) = explode(',', $formdata->category);
        $qcobject->move_questions_and_delete_category($formdata->delete, $tocategoryid);
        $thispageurl->remove_params('cat', 'category');
        redirect($thispageurl);
    }
} else {
    $questionstomove = 0;
}

if ($qcobject->catform->is_cancelled()) {
    redirect($thispageurl);
} else if ($catformdata = $qcobject->catform->get_data()) {
    $catformdata->infoformat = $catformdata->info['format'];
    $catformdata->info       = $catformdata->info['text'];
    if (!$catformdata->id) {//new category
        $qcobject->add_category($catformdata->parent, $catformdata->name,
                $catformdata->info, false, $catformdata->infoformat);
    } else {
        $qcobject->update_category($catformdata->id, $catformdata->parent,
                $catformdata->name, $catformdata->info, $catformdata->infoformat);
    }
    redirect($thispageurl);
} else if ((!empty($param->delete) and (!$questionstomove) and confirm_sesskey())) {
    $qcobject->delete_category($param->delete);//delete the category now no questions to move
    $thispageurl->remove_params('cat', 'category');
    redirect($thispageurl);
}

if ($param->edit) {
    $PAGE->navbar->add(get_string('editingcategory', 'question'));
}

$PAGE->set_title(get_string('editcategories', 'question'));
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

// Display the UI.
if (!empty($param->edit)) {
    $qcobject->edit_single_category($param->edit);
} else if ($questionstomove){
    $qcobject->display_move_form($questionstomove, $category);
} else {
    // Display the user interface.
    $qcobject->display_user_interface();
}
echo $OUTPUT->footer();
