<?php

namespace CustomerManagementFramework\CustomerView;

use CustomerManagementFramework\Model\CustomerInterface;
use CustomerManagementFramework\View\Formatter\ViewFormatterInterface;

interface CustomerViewInterface
{
    /**
     * @return ViewFormatterInterface
     */
    public function getViewFormatter();

    /**
     * @param CustomerInterface $customer
     * @return string|null
     */
    public function getOverviewTemplate(CustomerInterface $customer);

    /**
     * Determines if customer has a detail view or if pimcore object should be openend directly
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function hasDetailView(CustomerInterface $customer);

    /**
     * @param CustomerInterface $customer
     * @return string|null
     */
    public function getDetailviewTemplate(CustomerInterface $customer);

    /**
     * @param CustomerInterface $customer
     * @return array
     */
    public function getDetailviewData(CustomerInterface $customer);

    /**
     * @param string $value
     * @return string
     */
    public function translate($value);
}
