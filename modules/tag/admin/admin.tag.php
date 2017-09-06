<?php
/**
 * XOOPS tag management module
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright   	The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license     	General Public License version 3
 * @author      	Simon Roberts <wishcraft@users.sourceforge.net>
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @subpackage  	tag
 * @description 	XOOPS tag management module
 * @version			2.4.1
 * @link        	https://sourceforge.net/projects/chronolabs/files/XOOPS%202.5/Modules/tag
 * @link        	https://sourceforge.net/projects/chronolabs/files/XOOPS%202.6/Modules/tag
 * @link			https://sourceforge.net/p/xoops/svn/HEAD/tree/XoopsModules/tag
 * @link			http://internetfounder.wordpress.com
 */

include_once __DIR__ . '/header.php';
xoops_cp_header();

xoops_load("XoopsFormLoader");
xoops_load('XoopsPageNav');

$indexAdmin = new ModuleAdmin();

echo $indexAdmin->addNavigation(basename(__FILE__));
echo $indexAdmin->renderIndex();

$limit = 10;
$modid = intval( empty($_GET['modid']) ? @$_POST['modid'] : $_GET['modid'] );
$start = intval( empty($_GET['start']) ? @$_POST['start'] : $_GET['start'] );
$status = intval( empty($_GET['status']) ? @$_POST['status'] : $_GET['status'] );

$tag_handler =& xoops_getmodulehandler("tag", $xoopsModule->getVar("dirname"));

if (!empty($_POST['tags'])) {
    foreach ($_POST['tags'] as $tag => $tag_status) {
        $tag_obj =& $tag_handler->get($tag);
        if (!is_object($tag_obj) || !$tag_obj->getVar("tag_id")) continue;
        if ($tag_status < 0) {
            $tag_handler->delete($tag_obj);
        } elseif ($tag_status != $tag_obj->getVar("tag_status")) {
            $tag_obj->setVar("tag_status", $tag_status);
            $tag_handler->insert($tag_obj);
        }
    }
    redirect_header("admin.tag.php?modid={$modid}&amp;start={$start}&amp;status={$status}", 2);
    exit();
}

$sql  = "    SELECT tag_modid, COUNT(DISTINCT tag_id) AS count_tag";
$sql .= "    FROM " . $xoopsDB->prefix("tag_link");
$sql .= "    GROUP BY tag_modid";
$counts_module = array();
$module_list = array();
if ( ($result = $xoopsDB->query($sql)) == false) {
    xoops_error($xoopsDB->error());
} else {
    while ($myrow = $xoopsDB->fetchArray($result)) {
        $counts_module[$myrow["tag_modid"]] = $myrow["count_tag"];
    }
    if (!empty($counts_module)) {
        $module_handler =& xoops_gethandler("module");
        $module_list = $module_handler->getList(new Criteria("mid", "(" . implode(", ", array_keys($counts_module)) . ")", "IN"));
    }
}

$opform = new XoopsSimpleForm('', 'moduleform', xoops_getenv("PHP_SELF"), "get");
$tray = new XoopsFormElementTray('');
$mod_select = new XoopsFormSelect(_SELECT, 'modid', $modid);
$mod_select->addOption(0, _ALL);
foreach ($module_list as $module => $module_name) {
    $mod_select->addOption($module, $module_name." (" . $counts_module[$module] . ")");
}
$tray->addElement($mod_select);
$status_select = new XoopsFormRadio("", 'status', $status);
$status_select->addOption(-1, _ALL);
$status_select->addOption(0, TAG_AM_ACTIVE);
$status_select->addOption(1, TAG_AM_INACTIVE);
$tray->addElement($status_select);
$tray->addElement(new XoopsFormButton("", "submit", _SUBMIT, "submit"));
$opform->addElement($tray);
$GLOBALS['xoopsTpl']->assign("opform", $opform->render());
$GLOBALS['xoopsTpl']->assign("formuri", $_SERVER["PHP_SELF"]);

$criteria = new CriteriaCompo();
$criteria->setSort("a");
$criteria->setOrder("ASC");
if ($status >= 0) {
	$criteria->add( new Criteria("o.tag_status", $status) );
}
if (!empty($modid)) {
	$criteria->add( new Criteria("l.tag_modid", $modid) );
}
$count_tag = $tag_handler->getCount($criteria);
$nav = new XoopsPageNav($count_tag, $limit, $start, "start", "modid={$modid}&amp;status={$status}");
$GLOBALS['xoopsTpl']->assign('pagenav', $nav->renderNav(4));
$criteria->setStart($start);
$criteria->setLimit($limit);
$GLOBALS['xoopsTpl']->assign('tags', $tag_handler->getByLimit($criteria, false));
$GLOBALS['xoopsTpl']->assign('modid', $modid);
$GLOBALS['xoopsTpl']->assign('start', $start);
$GLOBALS['xoopsTpl']->assign('status', $status);

echo $GLOBALS['xoopsTpl']->display(dirname(__DIR__) . '/templates/admin/tag_admin.html');

include_once __DIR__ . '/footer.php';
?>