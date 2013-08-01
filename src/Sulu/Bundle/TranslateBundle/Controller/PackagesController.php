<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;

/**
 * Makes the translation catalogues accessible trough an REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class PackagesController extends FOSRestController
{
    /**
     * Lists all the catalogues or filters the catalogues by parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackagesAction()
    {
        $response = array();

        $sortOrder = $this->getRequest()->get('sortOrder', 'asc');
        $sortBy = $this->getRequest()->get('sortBy', 'id');
        $page = $this->getRequest()->get('page', 1);
        $pageSize = $this->getRequest()->get('pageSize');
        $fields = $this->getRequest()->get('fields');
        $fields = ($fields != null) ? explode(',', $fields) : null;

        // Calculate limit and offset
        $limit = $pageSize;
        $offset = ($pageSize != null) ? $pageSize * ($page - 1) : null;

        /** @var array $packages */
        $packages = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->findBy(array(), array($sortBy => $sortOrder), $limit, $offset); //TODO findAll more performant?

        $response['total'] = count($packages);
        if ($fields != null) {
            // Walk through all packages and only extract informations from given fields
            foreach ($packages as $package) {
                //TODO use reflections?
                /** @var Package $package */
                $item = array();
                if (in_array('name', $fields)) {
                    $item['name'] = $package->getName();
                }
                if (in_array('id', $fields)) {
                    $item['id'] = $package->getId();
                }
                $response['items'][] = $item;
            }
        } else {
            $response['items'] = $packages;
        }

        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the package with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackageAction($id)
    {
        $package = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->find($id);

        $view = $this->view($package, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new catalogue
     */
    public function postPackagesAction()
    {
        $em = $this->getDoctrine()->getManager();

        $languages = $this->getRequest()->get('languages');

        $package = new Package();
        $package->setName($this->getRequest()->get('name'));

        if ($languages != null) {
            foreach ($languages as $language) {
                $catalogue = new Catalogue();
                $catalogue->setCode($language);
                $catalogue->setPackage($package);
                $package->addCatalogue($catalogue);
                $em->persist($catalogue);
            }
        }

        $em->persist($package);
        $em->flush();

        $view = $this->view($package, 200);

        return $this->handleView($view);
    }

    /**
     * Update the existing package or create a new one with the given id,
     * if the package with the given id is not yet existing.
     * @param integer $id The id of the package to update
     */
    public function putPackagesAction($id)
    {
        $name = $this->getRequest()->get('name');
        $languages = $this->getRequest()->get('languages');

        /** @var Package $package */
        $package = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Package')
            ->find($id);

        $package->setName($name);

        $this->getDoctrine()->getManager()->flush();

        $view = $this->view($package);
        return $this->handleView($view);
    }
}
