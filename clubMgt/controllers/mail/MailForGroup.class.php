<?php
/** ****************************************************************************
 *  MailForGroup                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements pour l'affichage de la messagerie pour
 *      envoyer à un ensemble de personnes par rapport à leur groupe.
 *
 *
 *
 *
 *
 *
 *
 * ****************************************************************************
 *  (C) 2014-2023 Catherine Burvelle <contact.burvelle@free.fr>
 * ****************************************************************************
 */
class MailForGroup extends MailSpecificationImpl
{

    private $flux;

    private $group_list;

    // Comma separated list of groups to be displayed.
    function __construct(string $grpList = 'ALL')
    {
        parent::__construct();
        $this->name = "group";
        $this->selection = GROUP_ID_NONE;
        $this->group_list = trim($grpList, ',');
        $this->flux = array();
    }

    protected function getButtons()
    {
        $table = array();
        $table[] = $this->getButton("grp_name");
        $table[] = $this->getButton("coach_name");
        $table[] = $this->getButton("day_time", true);
        $table[] = $this->getButton("gym_firstname");
        $table[] = $this->getButton("gym_name");
        $table[] = $this->getButton("respA_firstname");
        $table[] = $this->getButton("respA_name");
        $table[] = $this->getButton("receipt");

        return $table;
    }

    protected function getTplView($active)
    {

        // Get user's selected Event Type (year)
        $user_config = ClubMgtUserConfig::load();
        $current_evt = $user_config->get_evt_type();

        $tpl = new FileTemplate("clubMgt/MailForGroupView.tpl");
        if ($this->group_list == 'ALL')
        {
            $grp = Util::build_groups($this->selection, $current_evt);
        }
        else
        {
            $grp = $this->getGroups();
        }
        $tpl->put('group1', $grp[1]); // loisirs
        $tpl->put('group2', $grp[2]); // compet
        $tpl->put('group3', $grp[3]); // adultes
        $tpl->put_all(array("name" => $this->getName()));

        return $tpl;
    }

    /*
     * Calcul les donnees necessaires pour completer le message
     * @return un tableau contenant un tableau associatif avec
     * toutes les info necessaires pour chaque message.
     */
    public function retreiveValues(HTTPRequestCustom $request)
    {
        // Get user's selected Event Type (year)
        $user_config = ClubMgtUserConfig::load();
        $current_evt = $user_config->get_evt_type();

        $values = array();
        $all_grp = $request->get_postarray('selected_' . $this->getName(), array());

        if (empty($all_grp)) return $values;

        $group_list = array();
        foreach ($all_grp as $grp)
        {
            $group_list[] = $grp;
            try
            {
                $grp_filter = "%;" . $grp . ";%";

                $allGym = ClubMgtDBService::select(
                        "SELECT r.id AS resp_id, r.respA_email, r.respA_name, r.respA_firstname, r.respA_tel, r.respB_email, r.respB_name, r.respB_firstname, "
                        . "r.respB_tel, g.name AS gym_name, g.firstname AS gym_firstname, g.email AS gym_email" .
                        "  FROM " . ClubMgtSetup::$clubMgt_table_responsible . " r RIGHT JOIN (" . ClubMgtSetup::$clubMgt_table_gymnaste . " g JOIN " .
                        ClubMgtSetup::$clubMgt_table_file . " f ON f.gym_id=g.id )  ON r.id=f.resp_id WHERE f.evt_type=:etype AND f.group_id_list LIKE :gid",
                        array('etype' => $current_evt, 'gid' => $grp_filter));

                $grpInfo = ClubMgtDBService::select_single_row(
                        ClubMgtSetup::$clubMgt_table_group,
                        array("coach_name", "grp_name", "prof_uid"),
                        "WHERE id=:id",
                        array('id' => $grp));

                $lessonInfo = ClubMgtDBService::select(
                        'SELECT ' . ClubMgtSetup::$clubMgt_table_lesson . '.* ' . ' FROM ' . ClubMgtSetup::$clubMgt_table_group_lesson_assoc .
                        ' INNER JOIN ' . ClubMgtSetup::$clubMgt_table_lesson . ' ON ' . ClubMgtSetup::$clubMgt_table_group_lesson_assoc . '.lesson_id=' .
                        ClubMgtSetup::$clubMgt_table_lesson . '.id' . ' WHERE ' . ClubMgtSetup::$clubMgt_table_group_lesson_assoc . '.grp_id=' . $grp);
                $disp = '<UL>';
                while ($l = $lessonInfo->fetch())
                {
                    $disp .= "<LI>" . Util::getLessonDisplay($l) . "</LI>";
                    $coachname = strtoupper(Util::get_display_name($l['prof_uid']));
                    $this->coaches[$coachname] = Util::get_email($l["prof_uid"]);
                }
                $disp .= "</UL>";
                $grpInfo['day_time'] = $disp;
                $grpCoachName = Util::get_display_name($grpInfo["prof_uid"]);
                $this->coaches[strtoupper($grpCoachName)] = Util::get_email($grpInfo["prof_uid"]);
                $grpInfo['coach_name'] = $grpCoachName;

                while ($g = $allGym->fetch())
                {
                    $url = new Url("index.php?url=/family/show/".$g['resp_id']."&receipt=".$current_evt);
                    $g['receipt'] = '<a href="'.$url->absolute().'">facture</a>';
                    //Debug::stop($g['receipt']);
                    $values[] = array_merge($g, $grpInfo);
                }
            } catch (Exception $ex)
            {
                // Debug::dump($ex);
            }
        }
        $flux_resp = ClubMgtDBService::select_rows(
                ClubMgtSetup::$clubMgt_table_group,
                array('stream_id'),
                "WHERE id IN (" . implode(',', $group_list) . ")");
        while ($fl = $flux_resp->fetch())
        {
            $this->flux[] = $fl['stream_id'];
        }
        return $values;
    }

    public function getArchiveFlux(): array
    {
        if (empty($this->flux))
        {
            return parent::getArchiveFlux();
        }
        return $this->flux;
    }

    private function getGroups(): array
    {
        $none = count($this->selection) < 1 ? 1 : 0;
        $tl = strval(CAT_ID_NONE) . ';' . strval(CAT_ID_NONE) . ';' . strval(CAT_ID_NONE);
        $groups = array(
            1 => array(0 => array('id' => GROUP_ID_NONE, 'grp_name' => 'aucun', 'kind' => 0, 'sel' => $none, 'tarif_list' => $tl)),
            2 => array(0 => array('id' => GROUP_ID_NONE, 'grp_name' => 'aucun', 'kind' => 0, 'sel' => $none, 'tarif_list' => $tl)),
            3 => array(0 => array('id' => GROUP_ID_NONE, 'grp_name' => 'aucun', 'kind' => 0, 'sel' => $none, 'tarif_list' => $tl)));

        $gps = ClubMgtDBService::select_rows(
                ClubMgtSetup::$clubMgt_table_group,
                array('id', 'grp_name', 'kind', 'tarif_list'),
                'WHERE id IN (' . $this->group_list . ') ORDER BY kind, grp_name');

        while ($c = $gps->fetch())
        {
            if ($c['id'] == GROUP_ID_NONE) continue;
            $c['sel'] = strpos($this->selection, ';' . $c['id'] . ';') === FALSE ? 0 : 1;
            $groups[$c['kind']][] = $c;
        }

        return $groups;
    }
}

?>