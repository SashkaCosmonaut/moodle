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
 * A class for representing question categories.
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

// number of categories to display on page
define('QUESTION_PAGE_LENGTH', 25);

require_once($CFG->libdir . '/listlib.php');
require_once($CFG->dirroot . '/question/category_form.php');
require_once($CFG->dirroot . '/question/move_form.php');


/**
 * Class representing a list of question categories
 *
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_category_list extends moodle_list {
    public $table = "question_categories";
    public $listitemclassname = 'question_category_list_item';
    /**
     * @var reference to list displayed below this one.
     */
    public $nextlist = null;
    /**
     * @var reference to list displayed above this one.
     */
    public $lastlist = null;

    public $context = null;
    public $sortby = 'parent, sortorder, name';

    /**
     * @var bool Флаг, отображающий, активирован ли режим перемещения категории.
     */
    public $movementmode = false;

    public function __construct($type='ul', $attributes='', $editable = false, $pageurl=null, $page = 0, $pageparamname = 'page', $itemsperpage = 20, $context = null){
        parent::__construct('ul', '', $editable, $pageurl, $page, 'cpage', $itemsperpage);
        $this->context = $context;
    }

    public function get_records() {
        $this->records = get_categories_for_contexts($this->context->id, $this->sortby);
    }

    /**
     * The overridden method which returns html string of the list.
     *
     * @param integer $indent depth of indentation.
     */
    public function to_html($indent=0, $extraargs=array()) {
        if (count($this->items)) {
            $tabs = str_repeat("\t", $indent);
            $html = '';

            foreach ($this->items as $item) {
                if ($this->editable) {
                    $item->set_icon_html();
                }
                if ($itemhtml = $item->to_html($indent+1, $extraargs)) {
                    $html .= "$tabs\t<li".((!empty($item->attributes))?(' '.$item->attributes):'').">";
                    $html .= $itemhtml;
                    $html .= "</li>\n";
                }
            }
        } else {
            $html = '';
        }
        if ($html) { //if there are list items to display then wrap them in ul / ol tag.
            $tabs = str_repeat("\t", $indent);
            $html = $tabs.'<'.$this->type.((!empty($this->attributes))?(' '.$this->attributes):'').">\n".$html;
            $html .= $tabs."</".$this->type.">\n";
        } else {
            $html ='';
        }
        return $html;
    }

    /**
     * Перевести список в режим перемещения указанной категории.
     */
    public function set_movement_mode() {
        $this->movementmode = true;
    }

    /**
     * Отменить режим перемещения списка и перевести его в обычный режим.
     */
    public function cancel_movement_mode() {
        $this->movementmode = false;
    }

    /**
     * Переместить запись в списке после другой записи в данном списке.
     * @param integer $movedrecord Идентификатор перемещаемой записи списка.
     * @param integer $upperrecord Идентификатор записи, после которой вставляется перемещаемая запись.
     */
    public function move_item_after($movedrecord, $upperrecord) {
        $this->movementmode = false;
    }

    /**
     * Переместить запись в качестве дочверней другой записи.
     * @param integer $movedrecord Идентификатор перемещаемой записи списка.
     * @param integer $parentrecord Идентификатор родительской записи.
     */
    public function move_item_in($movedrecord, $parentrecord) {
        $this->movementmode = false;
    }
}


/**
 * An item in a list of question categories.
 *
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_category_list_item extends list_item {

    /**
     * Set icons to category in the list.
     */
    public function set_icon_html(){
        $category = $this->item;
        $url = new moodle_url('/question/category.php', ($this->parentlist->pageurl->params() + array('edit'=>$category->id)));
        $this->icons['edit']= $this->image_icon(get_string('editthiscategory', 'question'), $url, 'edit');

        // Generate url for the link of the icon and set this icon.
        $url = new moodle_url($this->parentlist->pageurl, (array('sesskey'=>sesskey(), 'move'=>$this->id)));
        $this->icons['move'] = $this->image_icon(get_string('movecategory','question'), $url, 'dragdrop', 'i');
    }

    public function item_html($extraargs = array()){
        global $CFG, $OUTPUT;
        $str = $extraargs['str'];
        $category = $this->item;

        $editqestions = get_string('editquestions', 'question');

        // Each section adds html to be displayed as part of this list item.
        $questionbankurl = new moodle_url('/question/edit.php', $this->parentlist->pageurl->params());
        $questionbankurl->param('cat', $category->id . ',' . $category->contextid);
        $catediturl = new moodle_url($this->parentlist->pageurl, array('edit' => $this->id));
        $item = '';
        $item .= html_writer::tag('b', html_writer::link($catediturl,
                format_string($category->name, true, array('context' => $this->parentlist->context)),
                array('title' => $str->edit))) . ' ';
        $item .= html_writer::link($questionbankurl, '(' . $category->questioncount . ')',
                array('title' => $editqestions)) . ' ';
        $item .= format_text($category->info, $category->infoformat,
                array('context' => $this->parentlist->context, 'noclean' => true));

        // don't allow delete if this is the last category in this context.
        if (count($this->parentlist->records) != 1) {
            $deleteurl = new moodle_url($this->parentlist->pageurl, array('delete' => $this->id, 'sesskey' => sesskey()));
            $item .= html_writer::link($deleteurl,
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'),
                            'class' => 'iconsmall', 'alt' => $str->delete)),
                    array('title' => $str->delete));
        }

        return $item;
    }

    /**
     * Get image icon from specified folder with a link for category.
     *
     * @param string $action Describes the action of icon.
     * @param string $url Url for the link of icon.
     * @param string $icon Icon name.
     * @param string string $folder Folder where icon placed.
     * @return string HTML code of icon and its link.
     */
    public function image_icon($action, $url, $icon, $folder = 't') {
        global $OUTPUT;
        return '<a title="' . s($action) .'" href="'.$url.'">
                <img src="' . $OUTPUT->pix_url($folder.'/'.$icon) . '" class="iconsmall" alt="' . s($action). '" /></a> ';
    }

    /**
     * Получить html код для вставки области для перемещения в нее элемента списка.
     * @param string $movingurl Адрес ссылки вставляемой области.
     * @param array $attributes Массив дополнительных параметров ссылки.
     * @return string HTML код вставки области для перемещения в нее элемента списка.
     */
    public static function get_move_in_html($movingurl, $attributes = array()) {
        global $OUTPUT;

        $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
        return html_writer::link($movingurl, $OUTPUT->render($movingpix), $attributes);
    }
}


/**
 * Class representing q question category
 *
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_category_object {

    /**
     * @var array common language strings.
     */
    public $str;

    /**
     * @var array nested lists to display categories.
     */
    public $editlists = array();
    public $newtable;
    public $tab;
    public $tabsize = 3;

    /**
     * @var moodle_url Object representing url for this page
     */
    public $pageurl;

    /**
     * @var question_category_edit_form Object representing form for adding / editing categories.
     */
    public $catform;

    /**
     * @var int Идентификатор перемещаемой категории.
     */
    public $movedcatid = 0;

    /**
     * Constructor
     *
     * Gets necessary strings and sets relevant path information
     */
    public function question_category_object($page, $pageurl, $contexts, $currentcat, $defaultcategory, $todelete, $addcontexts) {
        global $CFG, $COURSE, $OUTPUT;

        $this->tab = str_repeat('&nbsp;', $this->tabsize);

        $this->str = new stdClass();
        $this->str->course         = get_string('course');
        $this->str->category       = get_string('category', 'question');
        $this->str->categoryinfo   = get_string('categoryinfo', 'question');
        $this->str->questions      = get_string('questions', 'question');
        $this->str->add            = get_string('add');
        $this->str->delete         = get_string('delete');
        $this->str->moveup         = get_string('moveup');
        $this->str->movedown       = get_string('movedown');
        $this->str->edit           = get_string('editthiscategory', 'question');
        $this->str->hide           = get_string('hide');
        $this->str->order          = get_string('order');
        $this->str->parent         = get_string('parent', 'question');
        $this->str->add            = get_string('add');
        $this->str->action         = get_string('action');
        $this->str->top            = get_string('top');
        $this->str->addcategory    = get_string('addcategory', 'question');
        $this->str->editcategory   = get_string('editcategory', 'question');
        $this->str->cancel         = get_string('cancel');
        $this->str->editcategories = get_string('editcategories', 'question');
        $this->str->page           = get_string('page');

        $this->pageurl = $pageurl;

        $this->initialize($page, $contexts, $currentcat, $defaultcategory, $todelete, $addcontexts);
    }

    /**
     * Initializes this classes general category-related variables
     */
    public function initialize($page, $contexts, $currentcat, $defaultcategory, $todelete, $addcontexts) {
        $lastlist = null;
        foreach ($contexts as $context){
            $this->editlists[$context->id] = new question_category_list('ul', '', true, $this->pageurl, $page, 'cpage', QUESTION_PAGE_LENGTH, $context);
            $this->editlists[$context->id]->lastlist =& $lastlist;
            if ($lastlist!== null){
                $lastlist->nextlist =& $this->editlists[$context->id];
            }
            $lastlist =& $this->editlists[$context->id];
        }

        $count = 1;
        $paged = false;
        foreach ($this->editlists as $key => $list){
            list($paged, $count) = $this->editlists[$key]->list_from_records($paged, $count);
        }
        $this->catform = new question_category_edit_form($this->pageurl, compact('contexts', 'currentcat'));
        if (!$currentcat){
            $this->catform->set_data(array('parent'=>$defaultcategory));
        }
    }

    /**
     * Displays the user interface
     *
     */
    public function display_user_interface() {

        /// Interface for editing existing categories
        $this->output_edit_lists();


        echo '<br />';
        /// Interface for adding a new category:
        $this->output_new_table();
        echo '<br />';

    }

    /**
     * Outputs a table to allow entry of a new category
     */
    public function output_new_table() {
        $this->catform->display();
    }

    /**
     * Outputs a list to allow editing/rearranging of existing categories
     *
     * $this->initialize() must have already been called
     *
     */
    public function output_edit_lists() {
        global $OUTPUT;

        echo $OUTPUT->heading_with_help(get_string('editcategories', 'question'), 'editcategories', 'question');

        foreach ($this->editlists as $context => $list){
            $listhtml = $list->to_html(0, array('str'=>$this->str));
            if ($listhtml){
                echo $OUTPUT->box_start('boxwidthwide boxaligncenter generalbox questioncategories contextlevel' . $list->context->contextlevel);
                $fullcontext = context::instance_by_id($context);
                echo $OUTPUT->heading(get_string('questioncatsfor', 'question', $fullcontext->get_context_name()), 3);

                echo $this->get_movement_message($list);  // Выводим сообщение об отмене перемещения, если оно идет.

                echo $listhtml;
                echo $OUTPUT->box_end();
            }
        }
        echo $list->display_page_numbers();
     }

    /**
     * Выводит сообщение о том, что идет перемещение категории, а также ссылку для его отмены.
     * @param question_category_list $list Отображаемый список.
     * @return string Строка с сообщением о перемещении и ссылкой его отмены.
     */
    public function get_movement_message($list) {
        // Если идет перемещение и в данном списке содержится перемещаемая категория
        if ($this->movedcatid && $item = $list->find_item($this->movedcatid, true)) {
            $cancelstring = get_string('movecategory','question').': ';     // Добавляем пояснение
            $cancelstring .= html_writer::tag('b',$item->name);             // Добавляем имя категории

            $url = new moodle_url($this->pageurl, (array('sesskey'=>sesskey(), 'cancel'=>1)));  // Формируем URL для "Отмены"

            $cancelstring .= ' ('.html_writer::link($url, get_string('cancel')).')';    // Добавляем ссылку "Отмена"
            return $cancelstring; // Выводим сообщение
        }
        return '';
    }

    /**
     * gets all the courseids for the given categories
     *
     * @param array categories contains category objects in  a tree representation
     * @return array courseids flat array in form categoryid=>courseid
     */
    public function get_course_ids($categories) {
        $courseids = array();
        foreach ($categories as $key=>$cat) {
            $courseids[$key] = $cat->course;
            if (!empty($cat->children)) {
                $courseids = array_merge($courseids, $this->get_course_ids($cat->children));
            }
        }
        return $courseids;
    }

    public function edit_single_category($categoryid) {
    /// Interface for adding a new category
        global $COURSE, $DB;
        /// Interface for editing existing categories
        if ($category = $DB->get_record("question_categories", array("id" => $categoryid))) {

            $category->parent = "$category->parent,$category->contextid";
            $category->submitbutton = get_string('savechanges');
            $category->categoryheader = $this->str->edit;
            $this->catform->set_data($category);
            $this->catform->display();
        } else {
            print_error('invalidcategory', '', '', $categoryid);
        }
    }

    /**
     * Sets the viable parents
     *
     *  Viable parents are any except for the category itself, or any of it's descendants
     *  The parentstrings parameter is passed by reference and changed by this function.
     *
     * @param    array parentstrings a list of parentstrings
     * @param   object category
     */
    public function set_viable_parents(&$parentstrings, $category) {

        unset($parentstrings[$category->id]);
        if (isset($category->children)) {
            foreach ($category->children as $child) {
                $this->set_viable_parents($parentstrings, $child);
            }
        }
    }

    /**
     * Gets question categories
     *
     * @param    int parent - if given, restrict records to those with this parent id.
     * @param    string sort - [[sortfield [,sortfield]] {ASC|DESC}]
     * @return   array categories
     */
    public function get_question_categories($parent=null, $sort="sortorder ASC") {
        global $COURSE, $DB;
        if (is_null($parent)) {
            $categories = $DB->get_records('question_categories', array('course' => $COURSE->id), $sort);
        } else {
            $select = "parent = ? AND course = ?";
            $categories = $DB->get_records_select('question_categories', $select, array($parent, $COURSE->id), $sort);
        }
        return $categories;
    }

    /**
     * Deletes an existing question category
     *
     * @param int deletecat id of category to delete
     */
    public function delete_category($categoryid) {
        global $CFG, $DB;
        question_can_delete_cat($categoryid);
        if (!$category = $DB->get_record("question_categories", array("id" => $categoryid))) {  // security
            print_error('unknowcategory');
        }
        /// Send the children categories to live with their grandparent
        $DB->set_field("question_categories", "parent", $category->parent, array("parent" => $category->id));

        /// Finally delete the category itself
        $DB->delete_records("question_categories", array("id" => $category->id));
    }

    public function move_questions_and_delete_category($oldcat, $newcat){
        question_can_delete_cat($oldcat);
        $this->move_questions($oldcat, $newcat);
        $this->delete_category($oldcat);
    }

    public function display_move_form($questionsincategory, $category){
        global $OUTPUT;
        $vars = new stdClass();
        $vars->name = $category->name;
        $vars->count = $questionsincategory;
        echo $OUTPUT->box(get_string('categorymove', 'question', $vars), 'generalbox boxaligncenter');
        $this->moveform->display();
    }

    public function move_questions($oldcat, $newcat){
        global $DB;
        $questionids = $DB->get_records_select_menu('question',
                'category = ? AND (parent = 0 OR parent = id)', array($oldcat), '', 'id,1');
        question_move_questions_to_category(array_keys($questionids), $newcat);
    }

    /**
     * Creates a new category with given params
     */
    public function add_category($newparent, $newcategory, $newinfo, $return = false, $newinfoformat = FORMAT_HTML) {
        global $DB;
        if (empty($newcategory)) {
            print_error('categorynamecantbeblank', 'question');
        }
        list($parentid, $contextid) = explode(',', $newparent);
        //moodle_form makes sure select element output is legal no need for further cleaning
        require_capability('moodle/question:managecategory', context::instance_by_id($contextid));

        if ($parentid) {
            if(!($DB->get_field('question_categories', 'contextid', array('id' => $parentid)) == $contextid)) {
                print_error('cannotinsertquestioncatecontext', 'question', '', array('cat'=>$newcategory, 'ctx'=>$contextid));
            }
        }

        $cat = new stdClass();
        $cat->parent = $parentid;
        $cat->contextid = $contextid;
        $cat->name = $newcategory;
        $cat->info = $newinfo;
        $cat->infoformat = $newinfoformat;
        $cat->sortorder = 999;
        $cat->stamp = make_unique_id_code();
        $categoryid = $DB->insert_record("question_categories", $cat);
        if ($return) {
            return $categoryid;
        } else {
            redirect($this->pageurl);//always redirect after successful action
        }
    }

    /**
     * Updates an existing category with given params
     */
    public function update_category($updateid, $newparent, $newname, $newinfo, $newinfoformat = FORMAT_HTML, $redirect = true) {
        global $CFG, $DB;
        if (empty($newname)) {
            print_error('categorynamecantbeblank', 'question');
        }

        // Get the record we are updating.
        $oldcat = $DB->get_record('question_categories', array('id' => $updateid));
        $lastcategoryinthiscontext = question_is_only_toplevel_category_in_context($updateid);

        if (!empty($newparent) && !$lastcategoryinthiscontext) {
            list($parentid, $tocontextid) = explode(',', $newparent);
        } else {
            $parentid = $oldcat->parent;
            $tocontextid = $oldcat->contextid;
        }

        // Check permissions.
        $fromcontext = context::instance_by_id($oldcat->contextid);
        require_capability('moodle/question:managecategory', $fromcontext);

        // If moving to another context, check permissions some more.
        if ($oldcat->contextid != $tocontextid) {
            $tocontext = context::instance_by_id($tocontextid);
            require_capability('moodle/question:managecategory', $tocontext);
        }

        // Update the category record.
        $cat = new stdClass();
        $cat->id = $updateid;
        $cat->name = $newname;
        $cat->info = $newinfo;
        $cat->infoformat = $newinfoformat;
        $cat->parent = $parentid;
        $cat->contextid = $tocontextid;
        $DB->update_record('question_categories', $cat);

        // If the category name has changed, rename any random questions in that category.
        if ($oldcat->name != $cat->name) {
            $where = "qtype = 'random' AND category = ? AND " . $DB->sql_compare_text('questiontext') . " = ?";

            $randomqtype = question_bank::get_qtype('random');
            $randomqname = $randomqtype->question_name($cat, false);
            $DB->set_field_select('question', 'name', $randomqname, $where, array($cat->id, '0'));

            $randomqname = $randomqtype->question_name($cat, true);
            $DB->set_field_select('question', 'name', $randomqname, $where, array($cat->id, '1'));
        }

        if ($oldcat->contextid != $tocontextid) {
            // Moving to a new context. Must move files belonging to questions.
            question_move_category_to_context($cat->id, $oldcat->contextid, $tocontextid);
        }

        if ($redirect) {    // Если требуется обновить страницу.
            // Cat param depends on the context id, so update it.
            $this->pageurl->param('cat', $updateid . ',' . $tocontextid);
            redirect($this->pageurl);
        }
    }

    /**
     * Обработка запроса на начало процесса перемещения категории.
     * @param int $movedcatid Идентификатор перемещаемой категории.
     */
    public function on_move($movedcatid) {
        if ($movedcatid) {
            require_sesskey();

            $this->movedcatid = $movedcatid;   // Сохраним идентификатор перемещаемой категории.

            // Переводим все имеющиеся списки в режим перемещения.
            foreach ($this->editlists as $list) {
                $list->set_movement_mode();
            }
        }
    }

    /**
     * Обработка запроса на отмену перемещения категории.
     * @param bool $iscanceled Надо ли отменить перемещение.
     */
    public function on_cancel_moove($iscanceled) {
        if ($iscanceled) {
            require_sesskey();

            $this->movedcatid = 0;  // Больше никакая категория не перемещается.

            // Отменяем во всех списках режим перемещения.
            foreach ($this->editlists as $list) {
                $list->cancel_movement_mode();
            }
        }
    }

    /**
     * Обработка запроса на вставку категории после указанной категории.
     * @param int $uppercatid Идентификатор категории, после окторой нужно вставить перемещаемую категорию.
     */
    public function on_move_to($uppercatid) {
        if ($uppercatid) {
            require_sesskey();

            foreach ($this->editlists as $list) {
                // Пробуем переместить перемещаемую категорию под указанной категорией, если она содержится в данном списке.
                $list->move_item_after($this->movedcatid, $uppercatid); // В $param->moveto хранится id категории, после которой будет расположена перемещаемая категория.
            }
        }
    }

    /**
     * Обработка запроса на перемещение категории в качестве подкатегории другой категории.
     * @param int $parentcatid Идентификатор родительской категории, для которой перемещаемая категнория станет дочерней..
     */
    public function on_move_in($parentcatid) {
        if ($parentcatid) {
            require_sesskey();

            foreach ($this->editlists as $list) {
                // Пробуем переместить перемещаемую категорию внутрь указанной категории, если она содержится в данном списке.
                $list->move_item_in($this->movedcatid, $parentcatid);  // В $param->movein хранится id категории, потомком которой хотим сделать перемещаемую категорию.
            }
        }
    }

    /**
     * Обработка запроса на перемещение категории в другой контекст.
     * @param int $newcontextid Идентификатор нового контекста.
     * @param int $uppercatid Идентификатор категории, за окторой вставили перемещаемую категорию.
     * @param int $parentcatid Идентификатор родительской категории, для которой перемещаемая категория стала дочерней.
     */
    public function on_move_to_context($newcontextid, $uppercatid = 0, $parentcatid = 0) {
        global $DB;

        if ($newcontextid) {
            require_sesskey();

            // Изменяем контекст у перемещаемой категории.
            $oldcat = $DB->get_record('question_categories', array('id' => $this->movedcatid), '*', MUST_EXIST);

            // Обновляем категорию, но без обновления страницы
            $this->update_category($this->movedcatid, '0,'.$newcontextid, $oldcat->name, $oldcat->info,FORMAT_HTML, false);

            $this->on_move_to($uppercatid);     // Проверить, если перемещаемую категорию еще и вставили за категорией.
            $this->on_move_in($parentcatid);    // Проверить, если перемещаемую категорию еще и сделали потомком категории.
        }
    }
}

/**
 * Отладочная печать указанной переменной с указанным сообщением
 * @param $smth Какая-то переменная, содержимое которой надо вывести
 * @param string $msg Поясняющее сообшение для вывода данных переменной
 */
function pre_print($smth, $msg="") {

    if ($msg != "") { echo "===== ".$msg.": =====<br/><br/>"; }

    ?>
    <pre>
            <?print_r($smth);?>
        </pre>
<?
}
