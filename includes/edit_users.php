<?php

$ldapSearchResults = array();

$selectedUser = new User($sqlDataBase);
$userDepartment = new Department($sqlDataBase);
$rate = new Rate($sqlDataBase);
$group = new Group($sqlDataBase);


if ($accessControl->GetPermissionLevel($authenticate->getAuthenticatedUser()->GetUserId(), AccessControl::RESOURCE_PAGE, $pages->GetPageId('Edit Users')) == AccessControl::PERM_ALLOW) {
    $selectedUser->LoadUser($authenticate->getAuthenticatedUser()->GetUserId());
} elseif ($accessControl->GetPermissionLevel($authenticate->getAuthenticatedUser()->GetUserId(), AccessControl::RESOURCE_PAGE, $pages->GetPageId('Edit Users')) == AccessControl::PERM_ADMIN) {
    if (isset($_POST['selected_user_id'])) {
        $selectedUser->LoadUser($_POST['selected_user_id']);
    }
}

//Only allow Users to change their own user profile or an admin
if ($selectedUser->GetUserId() == $authenticate->getAuthenticatedUser()->GetUserId() || $accessControl->GetPermissionLevel($authenticate->getAuthenticatedUser()->GetUserId(), AccessControl::RESOURCE_PAGE, $pages->GetPageId('Edit Users')) == AccessControl::PERM_ADMIN) {
    //If Modified user form
    if (isset($_POST['Modify'])) {
        //Update user info
        $selectedUser->SetFirst($_POST['first']);
        $selectedUser->SetLast($_POST['last']);
        $selectedUser->SetEmail($_POST['email']);
        $selectedUser->SetDepartmentId($_POST['department']);
        if ($accessControl->GetPermissionLevel($authenticate->getAuthenticatedUser()->GetUserId(), AccessControl::RESOURCE_PAGE, $pages->GetPageId('Edit Users')) == AccessControl::PERM_ADMIN) {
            $selectedUser->SetUserName($_POST['user_name']);
            $selectedUser->SetRateId($_POST['rate']);
            $selectedUser->SetStatusId($_POST['status']);
            $selectedUser->SetUserRoleId($_POST['user_role_id']);
            $selectedUser->SetGroupId($_POST['group']);
        }
        if(isset($_POST['user_cfop_id']))
        {
            $selectedUser->SetDefaultCfop($_POST['user_cfop_id']);
        }
        $selectedUser->UpdateUser();

    }

    if (isset($_POST['add_cfop'])) {
        $selectedUser->AddCfop($_POST['cfop_to_add']);
    }
}

//Block all other option to only allow Admins access
if ($accessControl->GetPermissionLevel($authenticate->getAuthenticatedUser()->GetUserId(), AccessControl::RESOURCE_PAGE, $pages->GetPageId('Edit Users')) == AccessControl::PERM_ADMIN) {
    // Submited New User
    if (isset($_POST['Create'])) {
        $selectedUser->CreateUser($_POST['user_name'], $_POST['first'], $_POST['last'], $_POST['email'], $_POST['department'], $_POST['group'], $_POST['rate'], $_POST['status'], $_POST['user_role_id'], $_POST['permissionGroup']);
    }

    if (isset($_POST['select_user'])) {
        $selectedUser->LoadUser($_POST['selected_user_id']);
    }
}
?>

<div class="alert alert-info">
    <h4>Edit Users</h4>

    <p>Add users</p>
</div>
<form action="./index.php?view=<?php echo $pages->GetPageId('Edit Users'); ?>" method=POST>
<div class="form-group">
    <?php
    if ($selectedUser->GetUserId() > 0) {
        echo '<input name="Modify" type="submit" class="btn btn-primary" id="Modify" value="Modify">';
    } else {
        echo '<input name="Create" type="submit" class="btn btn-primary" id="Submit" value="Create" >';
    }
    ?>
</div>

<div class="row-fluid">
<div class="col-md-6">
    <div class="well form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">User</label>

            <div class="col-sm-7">
                <select name="selected_user_id" class="form-control">
                    <option value="0">New User</option>
                    <?php
                    if ($accessControl->GetPermissionLevel($authenticate->getAuthenticatedUser()->GetUserId(), AccessControl::RESOURCE_PAGE, $pages->GetPageId('Edit Users')) == AccessControl::PERM_ADMIN) {
                        $allUsers = $selectedUser->GetAllUsers();
                    } else {
                        $allUsers = $selectedUser->GetGroupUsers($authenticate->getAuthenticatedUser()->getGroupId());
                    }

                    foreach ($allUsers as $id => $userToSelect) {
                        echo "<option value=" . $userToSelect["id"];
                        if ($userToSelect["id"] == $selectedUser->GetUserId()) {
                            echo " SELECTED";
                        }
                        echo ">" . $userToSelect["user_name"] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input name="select_user" type="submit" class="btn btn-primary" id="search" value="Select"/>
            </div>
        </div>
    </div>
    <br>

    <div class="well form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">Netid</label>

            <div class="col-sm-10">
                <input name="user_name" type="text" class="form-control"
                       value=<?php echo $selectedUser->GetUserName(); ?>>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">First</label>

            <div class="col-sm-10">
                <input name="first" type="text" class="form-control"
                       value=<?php echo $selectedUser->GetFirst(); ?>>

            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">Last</label>

            <div class="col-sm-10">
                <input name="last" type="text" class="form-control"
                       value=<?php echo $selectedUser->GetLast(); ?>>

            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">Mail</label>

            <div class="col-sm-10">
                <input name="email" type="email"
                       class="form-control" value=<?php echo $selectedUser->GetEmail(); ?>>

            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">Depart.</label>

            <div class="col-sm-10">

                <select name="department" class="form-control">
                    <?php
                    $departmentsList = $userDepartment->GetDepartmentList();
                    foreach ($departmentsList as $departmentInfo) {
                        echo "<option value=" . $departmentInfo['id'];
                        if ($departmentInfo['id'] == $selectedUser->GetDepartmentId()) {
                            echo " SELECTED";
                        }
                        echo " >" . $departmentInfo['department_name'] . "</option>";
                    }
                    ?>
                </select>

            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="editUser">CFOP</label>

            <div class="col-sm-8">

                <SELECT name="user_cfop_id" class="form-control">
                    <?php
                    $userCfopList = $selectedUser->ListCfops($selectedUser->GetUserId());
                    foreach ($userCfopList as $id => $cfopCodeInfo) {
                        echo "<option value=" . $cfopCodeInfo['id'];
                        if ($cfopCodeInfo['default_cfop']) {
                            echo " SELECTED";
                        }
                        echo ">" . UserCfop::formatCfop($cfopCodeInfo['cfop']) . "</option>";
                    }
                    ?>
                </SELECT>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-2"></div>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="cfop_to_add" placeholder="1-xxxxxx-xxxxxx-xxxxxx">
            </div>
            <div class="col-sm-2">
                <input type="submit" name="add_cfop" value="Add" class="btn btn-primary">
            </div>

        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="row-fluid">

        <div class="well form-horizontal">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="editUser">Rate</label>

                <div class="col-sm-10">
                    <select name="rate" class="form-control">
                        <?php

                        $listRates = $rate->GetRates();
                        foreach ($listRates as $id => $rate) {
                            echo "<option value=" . $rate['id'];
                            if ($rate['id'] == $selectedUser->GetRateId()) {
                                echo " SELECTED";
                            }
                            echo ">" . $rate['rate_name'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">

                <label class="col-sm-2 control-label" for="editUser">Group</label>

                <div class="col-sm-10">


                    <select name="group" class="form-control">
                        <?php
                        $listGroups = $group->GetGroupsList();
                        foreach ($listGroups as $id => $groupToSelect) {
                            echo "<option value=" . $groupToSelect['id'];
                            if ($selectedUser->GetGroupId() == $groupToSelect['id']) {
                                echo " SELECTED";
                            }
                            echo ">" . $groupToSelect['group_name'] . "</option>";
                        }
                        ?>
                    </select>

                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="editUser">Role</label>

                <div class="col-sm-10">
                    <select name="user_role_id" class="form-control">
                        <?php
                        $userRolesList = $selectedUser->GetUserRoles();
                        foreach ($userRolesList as $userRole) {
                            echo "<option value=" . $userRole['id'];
                            if ($selectedUser->GetUserRoleId() == $userRole['id']) {
                                echo " SELECTED";
                            }
                            echo ">" . $userRole['role_name'] . "</option>";
                        }
                        ?>
                    </select>

                </div>
            </div>

            <div class="form-group">

                <label class="col-sm-2 control-label" for="editUser">Status</label>

                <div class="col-sm-10">
                    <select name="status" class="form-control">
                        <?php
                        $userStatus = 2;
                        $queryUsersStatus = "SELECT statusname,id FROM status WHERE type=" . $userStatus;

                        foreach ($sqlDataBase->query($queryUsersStatus) as $usersStatus) {
                            echo "<option value=" . $usersStatus['id'];
                            if ($usersStatus['id'] == $selectedUser->GetStatusId()) {
                                echo " SELECTED";
                            }
                            echo ">" . $usersStatus['statusname'] . "</option>";
                        }
                        ?>
                    </select>

                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="editUser">Created</label>

                <div class="col-sm-10">
                    <h5>
                        <?php
                        echo $selectedUser->GetDateAdded();
                        ?>
                    </h5>

                </div>
            </div>
        </div>
    </div>
</form>
</div>

</div>
<div class="row-fluid">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>User Directory</h4>
            </div>
            <div class="body">
                <?php
                $usersFullInfoList = $selectedUser->GetAllUsersFullInfo();
                echo VisualizeData::ListSessionsTable($usersFullInfoList, array('First Name', 'Last Name', 'E-Mail', 'Group', 'Department'), array('first', 'last', 'email', 'group_name', 'department_name'), 'usersTable');

                ?>
            </div>
        </div>
    </div>
