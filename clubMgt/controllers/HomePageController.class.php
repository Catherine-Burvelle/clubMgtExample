<?php

/*
 * ***************************************************************************
 * HomePageController clubMgt project
 * ****************************************************************************
 *
 * File Description:
 *
 * Page d'accueil.
 *
 *
 *
 *
 *
 *
 *
 *
 * ****************************************************************************
 * (C) 2014 Catherine Burvelle <contact.burvelle@free.fr>
 * (C) 2015 Catherine Burvelle
 * ****************************************************************************
 */

class HomePageController extends AbstractController
{

    public function execute(HTTPRequestCustom $request)
    {
        $hp = new ClubMgtHomePageExtensionPoint();
        return new SiteDisplayResponse($hp->get_home_page()->get_view());
    }
}

?>