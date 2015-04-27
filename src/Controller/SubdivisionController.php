<?php

/**
 * @file
 * Contains \Drupal\address\Controller\SubdivisionController.
 */

namespace Drupal\address\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides route responses for subdivisions.
 */
class SubdivisionController extends ControllerBase {

  /**
   * Provides the subdivision add form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return array
   *   The subdivision add form.
   */
  public function addForm(RouteMatchInterface $routeMatch) {
    $addressFormat = $routeMatch->getParameter('address_format');
    $parent = $routeMatch->getParameter('parent');
    $values = array(
      'countryCode' => $addressFormat->getCountryCode(),
      'parentId' => $parent ? $parent->id() : NULL,
    );
    $subdivision = $this->entityManager()->getStorage('subdivision')->create($values);

    return $this->entityFormBuilder()->getForm($subdivision, 'add');
  }

  /**
   * Provides the subdivision list.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return array
   *   The subdivision list.
   */
  public function buildList(RouteMatchInterface $routeMatch) {
    $listBuilder = $this->entityManager()->getListBuilder('subdivision');
    $listBuilder->setAddressFormat($routeMatch->getParameter('address_format'));
    $listBuilder->setParent($routeMatch->getParameter('parent'));
    $build = array();
    $build['subdivision_table'] = $listBuilder->render();

    return $build;
  }

}
