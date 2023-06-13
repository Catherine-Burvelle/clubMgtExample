<?php
/** ****************************************************************************
 *  MailForLesson                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements pour l'affichage de la messagerie pour
 *      envoyer à un ensemble de personnes par rapport à leur cours.
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
class MailForLesson extends MailSpecificationImpl
{

    private $flux = array();

    function __construct()
    {
        parent::__construct();
        $this->name = "lesson";
        $this->selection = -1;
    }

    protected function getButtons()
    {
        $table = array();
        $table[] = $this->getButton("coach_name");
        $table[] = $this->getButton("day");
        $table[] = $this->getButton("start_time");
        $table[] = $this->getButton("location");
        $table[] = $this->getButton("elapse");
        $table[] = $this->getButton("day_time", true);
        $table[] = $this->getButton("gym_firstname");
        $table[] = $this->getButton("gym_name");
        $table[] = $this->getButton("respA_firstname");
        $table[] = $this->getButton("respA_name");
        return $table;
    }

    protected function getTplView($active)
    {
        // Get user's selected Event Type (year)
        $user_config = ClubMgtUserConfig::load();
        $current_evt = $user_config->get_evt_type();

        $tpl = new StringTemplate($this->LESSON_TPL);
        $lessons = array();
        $lessons[] = array('id' => 0, 'desc' => 'Aucun');
        $les = ClubMgtDBService::select_rows(
                ClubMgtSetup::$clubMgt_table_lesson,
                array('*'),
                'WHERE evt_type=:evt ORDER BY week_day, start_time',
                array('evt' => $current_evt));

        while ($c = $les->fetch())
        {
            $desc = Util::getLessonDisplay($c);
            $lessons[] = array('id' => $c['id'], 'desc' => $desc, 'selected' => $c['id'] == $this->selection ? 'selected' : '');
        }
        $tpl->put('lesson', $lessons);
        $tpl->put_all(array("name" => $this->getName()));

        return $tpl;
    }

    /*
     * Calcul les donnees necessaires pour completer le message.
     * @return un tableau contenant un tableau associatif avec
     * toutes les info corespondant à la liste de boutons retournée par getButtons.
     */
    public function retreiveValues(HTTPRequestCustom $request)
    {
        $values = array();
        $all_les = $request->get_postarray('selected_' . $this->getName(), array());
        if (empty($all_les)) return $values;

        // Get user's selected Event Type (year)
        $user_config = ClubMgtUserConfig::load();
        $current_evt = $user_config->get_evt_type();
        $grp_array_list = array();
        foreach ($all_les as $lesson_id)
        {
            try
            {
                // $lesson_id = $request->get_poststring('lesson');

                $les = ClubMgtDBService::select_single_row(ClubMgtSetup::$clubMgt_table_lesson, array('*'), 'WHERE id=' . $lesson_id);
                $les['day'] = $this->CLUB_LANG['day.' . $les['week_day']];

                $grpResp = ClubMgtDBService::select_rows(
                        ClubMgtSetup::$clubMgt_table_group_lesson_assoc,
                        array('grp_id'),
                        'WHERE lesson_id=:lid',
                        array('lid' => $lesson_id));
                $grpLst = '';
                while ($g = $grpResp->fetch())
                {
                    $grpLst .= ClubMgtSetup::$clubMgt_table_file . ".group_id_list LIKE '%;" . $g['grp_id'] . ";%' OR ";
                    $grp_array_list[] = $g['grp_id'];
                }
                $grpLst = substr($grpLst, 0, -3);

                $allGym = ClubMgtDBService::select(
                        "SELECT " . ClubMgtSetup::$clubMgt_table_responsible . ".respA_email, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respA_name, " . ClubMgtSetup::$clubMgt_table_responsible . ".respA_firstname, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respA_tel, " . ClubMgtSetup::$clubMgt_table_responsible . ".respB_email, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respB_name, " . ClubMgtSetup::$clubMgt_table_responsible . ".respB_firstname, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respB_tel, " . ClubMgtSetup::$clubMgt_table_gymnaste . ".name AS gym_name," . ClubMgtSetup::$clubMgt_table_gymnaste .
                        ".firstname AS gym_firstname," . ClubMgtSetup::$clubMgt_table_gymnaste . ".email AS gym_email" .
                        // ClubMgtSetup::$clubMgt_table_file.".validated
                        // AS gymnaste_status,".
                        // ClubMgtSetup::$clubMgt_table_file.".id AS
                        // file_id,".
                        // ClubMgtSetup::$clubMgt_table_file.".annotation
                        // AS annot".
                        "  FROM " . ClubMgtSetup::$clubMgt_table_responsible . " RIGHT JOIN (" . ClubMgtSetup::$clubMgt_table_gymnaste . " JOIN " .
                        ClubMgtSetup::$clubMgt_table_file . " ON " . ClubMgtSetup::$clubMgt_table_file . ".gym_id=" .
                        ClubMgtSetup::$clubMgt_table_gymnaste . ".id ) " . " ON " . ClubMgtSetup::$clubMgt_table_responsible . ".id=" .
                        ClubMgtSetup::$clubMgt_table_file . ".resp_id" . " WHERE " . ClubMgtSetup::$clubMgt_table_file . ".evt_type=:etype" . " AND ( " .
                        $grpLst . ")",
                        array('etype' => $current_evt));

                while ($g = $allGym->fetch())
                {
                    // Debug::dump($g);
                    $g = array_merge($g, $les);
                    $g['coach_name'] = Util::get_display_name($les['prof_uid']);
                    $g['day_time'] = Util::getLessonDisplay($les);
                    $this->coaches[strtoupper($g['coach_name'])] = Util::get_email($les["prof_uid"]);
                    $values[] = $g;
                }
            } // Try
            catch (Exception $e)
            {
                Debug::stop($e);
                // Nothing specific return empty table.
            }
        } // For

        $flux_resp = ClubMgtDBService::select_rows(
                ClubMgtSetup::$clubMgt_table_group,
                array('stream_id'),
                "WHERE id IN (:list)",
                array('list' => implode(',', $grp_array_list)));
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

    private $LESSON_TPL = "   <select id='list_{name}' >
		# START lesson #
			<option value={lesson.id} {lesson.selected}>{lesson.desc}</option>
		# END lesson #
		</select>";
}
?>