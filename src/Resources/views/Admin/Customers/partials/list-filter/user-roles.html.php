<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 *
 * @var array $clearUrlParams
 * @var \Pimcore\Model\DataObject\CustomerSegmentGroup[] $segmentGroups
 * @var array $filters
 * @var \CustomerManagementFrameworkBundle\CustomerView\CustomerViewInterface $customerView
 * @var \CustomerManagementFrameworkBundle\Model\CustomerView\FilterDefinition $filterDefinition
 * @var bool $preselected
 */
?>
<div class="row">
    <div class="col-xs-12">
        <div class="form-group">
            <label for="filterDefinition[allowedUserIds]"><?= $customerView->translate('Share with user') ?></label>
            <select
                id="filterDefinition[allowedUserIds]"
                name="filterDefinition[allowedUserIds][]"
                class="form-control plugin-select2"
                multiple="multiple"
                data-placeholder="<?= $customerView->translate('Share with user') ?>"
                data-select2-options='<?= json_encode(['allowClear' => false]) ?>'>
                <?php
                /** @noinspection PhpUndefinedMethodInspection */
                $users = (new \Pimcore\Model\User\Listing())->load();
                /** @var Pimcore\Model\User $user */
                foreach ($users as $user):
                    if($user->getType() !== 'user') continue;
                    ?><option value="<?= $user->getId() ?>"<?= ((boolval($preselected) && in_array($user->getId(), $filterDefinition->getAllowedUserIds())) ? ' selected="selected"' : '') ?>><?= $user->getName() . (!empty($user->getFirstname().$user->getLastname()) ? ' (' . trim($user->getFirstname() . ' ' . $user->getLastname()) . ')' : '') ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="form-group">
            <label for="filterDefinition[allowedRoleIds]"><?= $customerView->translate('Share with roles') ?></label>
            <select
                id="filterDefinition[allowedRoleIds]"
                name="filterDefinition[allowedRoleIds][]"
                class="form-control plugin-select2"
                multiple="multiple"
                data-placeholder="<?= $customerView->translate('Share with roles') ?>"
                data-select2-options='<?= json_encode(['allowClear' => false]) ?>'>
                <?php
                /** @noinspection PhpUndefinedMethodInspection */
                $roles = (new \Pimcore\Model\User\Role\Listing())->load();
                /** @var Pimcore\Model\User\Role $role */
                foreach ($roles as $role): ?><option value="<?= $role->getId() ?>"<?= ((boolval($preselected) && in_array($role->getId(), $filterDefinition->getAllowedUserIds())) ? ' selected="selected"' : '') ?>><?= $role->getName() ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
</div>