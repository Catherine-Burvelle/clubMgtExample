<?php
/** ****************************************************************************
 *  MailForRole                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements pour l'affichage de la messagerie pour
 *      envoyer à un ensemble de personnes par rapport à leur rôle.
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
class MailForRole extends MailSpecificationImpl
{

    function __construct()
    {
        parent::__construct();
        $this->name = "role";
        $this->selection = 0;
    }

    protected function getButtons()
    {
        $table = array();
        $table[] = $this->getButton("display_name");
        // $table[] = $this->getButton("role_name");

        return $table;
    }

    public function retreiveValues(HTTPRequestCustom $request)
    {
        $values = array();
        // $val = $request->get_poststring('status');
        $all_val = $request->get_postarray('selected_' . $this->getName());
        foreach ($all_val as $val)
        {
            $values = array_merge($values, Util::getGroupMembers($val));
        }
        return $values;
    }

    protected function getTplView($active)
    {
        $roles = array();
        $tpl = new StringTemplate($this->ROLE_TPL);
        $roles_lst = ClubMgtDBService::select_rows(DB_TABLE_GROUP, array('name'));

        while ($r = $roles_lst->fetch())
        {
            $roles[] = $r;
        }
        $tpl->put('role', $roles);
        $tpl->put_all(array("name" => $this->getName()));

        return $tpl;
    }

    private $ROLE_TPL = " 		<select id='list_{name}'>
 		# START role #
 			<option value={role.name}>{role.name}</option>
 		# END role #
 		</select>";
}
?>