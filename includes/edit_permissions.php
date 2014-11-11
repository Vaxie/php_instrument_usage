<?php

//Create lists for constant variables of access control to simplify code
$participantTypeList = array(AccessControl::PARTICIPANT_ROLE => "Role", AccessControl::PARTICIPANT_GROUP => "Group", AccessControl::PARTICIPANT_USER => "User");
$resourceTypeList = array(AccessControl::RESOURCE_DEVICE => "Devices", AccessControl::RESOURCE_PAGE => "Pages");
$permissionTypeList = array(AccessControl::PERM_ALLOW=>"Allow",AccessControl::PERM_DISALLOW=>"Block",AccessControl::PERM_ADMIN=>"Administrator", AccessControl::PERM_SUPERVISOR=>"Supervisor");

//Set initial page load default values
$selectedParticipantTypeId = AccessControl::PARTICIPANT_ROLE;
$selectedParticipantId = 0;
$selectedParticipantName = "";

//User clicked on Apply or Select participant then set selected php variables
if (isset($_POST['select_participant']) || isset($_POST['update_resource_access'])) {
    $selectedParticipantId = $_POST['participant_id'];
    $selectedParticipantTypeId = $_POST['selected_participant_type'];
}

//User clicked on select participant type then reset participant id to 0 (non selected) and set selected participant type to the dropdown selection
if (isset($_POST['select_participant_type'])) {
    $selectedParticipantTypeId = $_POST['selected_participant_type'];
    $selectedParticipantId = 0;
}

if(isset($_POST['update_resource_access']))
{
    if(isset($_POST['resourcesInfo']))
    {
        //User clicked on update resource access go through list and update access for each item according to user preference
        foreach($_POST['resourcesInfo'] as $resourceString)
        {
            list($resourceId,$resourceTypeId,$participantId,$participantTypeId)= explode("_",$resourceString);
            $accessControl->SetAccess($resourceTypeId,$resourceId,$participantTypeId,$participantId, $_POST[$resourceString]);
        }
    }

    //If checkbox array exists for delete resource access
    if(isset($_POST['deleteResourceAccess']))
    {
        //Go through each delete checkbox and see which resource access should be deleted
        foreach($_POST['deleteResourceAccess'] as $deleteResourceString)
        {
            list($resourceId,$resourceTypeId,$participantId,$participantTypeId)= explode("_",$deleteResourceString);
            $accessInfo = $accessControl->AccessExists($resourceTypeId,$resourceId,$participantTypeId,$participantId);
            $accessControl->DeleteAccess($accessInfo['id']);
        }
    }
}

//Add a role
if(isset($_POST['add_role']))
{
    $selectedParticipantId = $_POST['participant_id'];
    $selectedParticipantTypeId = $_POST['selected_participant_type'];
    $accessControl->CreateRole($_POST['create_role_name']);
}

//Set Access control
foreach($resourceTypeList as $resourceTypeId => $resourceTypeName)
{
    if(isset($_POST["add_access_".$resourceTypeId]))
    {
        $selectedParticipantTypeId = $_POST['selected_participant_type'];
        $selectedParticipantId = $_POST['participant_id'];
        $accessControl->SetAccess($resourceTypeId,$_POST["add_access_resource_id_".$resourceTypeId],$selectedParticipantTypeId,$selectedParticipantId,$_POST["add_access_permission_id_".$resourceTypeId]);
    }
}
?>

<div class="alert alert-info">
    <h4>Access Control</h4>
    <p>Control user access to pages and devices based on an access control list</p>
    <p>Access may be given based on the following permission layers in the following order, if permission does not exist for a certain layer then the next permissions layer is evaluated:</p>
    <p><b>User:</b> Permissions may be given to a specific user</p>
    <p><b>Group:</b> Permissions may be given to a specific group</p>
    <p><b>Role:</b> permissions may be given based on a specific role</p>
</div>
<form action="./index.php?view=<?php echo $pages->GetPageId('Edit Permissions'); ?>" method="POST">

<div class="row-fluid">
    <div class="col-md-6">
        <div class="well form-horizontal">
        <div class="form-group">
            <label class="col-sm-2 control-label">Layer</label>
            <div class="col-sm-5">
            <SELECT name="selected_participant_type" class="form-control">
                <?php
                foreach ($participantTypeList as $participantTypeId => $participantTypeName) {
                    echo "<option value=" . $participantTypeId;
                    if ($participantTypeId == $selectedParticipantTypeId) {
                        echo " SELECTED";
                    }
                    echo ">" . $participantTypeName . "</option>";
                }
                ?>
            </SELECT>
            </div>
            <div class="col-sm-5">
            <input type="submit" name="select_participant_type" value="Select" class="btn btn-primary">
            </div>
        </div>
       </div>

        <div class="alert alert-info">
            <p><b>Select</b> <?php echo $participantTypeList[$selectedParticipantTypeId]; ?> then modify permissions and click on <b>Apply</b></p>
        </div>
        <div class="well form-inline">
            <div class="form-group">
                <label class="control-label col-sm-3"><?php echo $participantTypeList[$selectedParticipantTypeId]; ?> Name</label>
                <div class="col-sm-5">
                    <SELECT name="participant_id" class="form-control">
                        <?php
                        $participantList = $accessControl->GetParticipantsList($selectedParticipantTypeId);
                        foreach ($participantList as $id => $participantInfo) {
                            echo "<option value=" . $participantInfo['participant_id'];
                            if ($selectedParticipantId == $participantInfo['participant_id']) {
                                $selectedParticipantName = $participantInfo['participant_name'];
                                echo " SELECTED";
                            }
                            echo ">" . $participantInfo['participant_name'] . "</option>";
                        }
                        ?>
                    </SELECT>
                </div>
                <div class="col-sm-4">
                    <input type="submit" name="select_participant" value="Select" class="btn btn-primary">
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">


        <?php
        if($selectedParticipantTypeId==AccessControl::PARTICIPANT_ROLE)
        {
            echo "<div class=\"well form-inline\">";
            echo "<h5>Create ".$participantTypeList[$selectedParticipantTypeId].":</h5>
            <input type=\"text\" name=\"create_role_name\" value=\"\" class=\"form-control\">
            <input type=\"submit\" name=\"add_role\" value=\"Create\" class=\"btn btn-primary\">";
            echo "</div>";
        }
?>
    </div>
    </div>
    <div class="row-fluid">
        <div class="form-group">
            <input type="submit" name="update_resource_access" value="Apply Permission Changes" class="btn btn-primary">
            </div>
        <?php
        //List access control for given participant
        if ($selectedParticipantId) {
            foreach ($resourceTypeList as $resourceTypeId => $resourceTypeName) {
                $accessList = $accessControl->GetAccessList($resourceTypeId, $selectedParticipantTypeId, $selectedParticipantId);

                echo "<div class=\"panel panel-default\">
                        <div class=\"panel-heading\">
                        <h4>" . $resourceTypeName . " ".$selectedParticipantName." Access Control:</h4>
                        </div>
                        <div class=\"panel-body\">";

                if($selectedParticipantTypeId==AccessControl::PARTICIPANT_GROUP || $selectedParticipantTypeId==AccessControl::PARTICIPANT_USER)
                {
                    echo "<div class=\"well form-inline\">";
                    echo "<h5>Access Control:";
                    echo "<SELECT name=\"add_access_resource_id_".$resourceTypeId."\">";
                    foreach ($accessList as $id => $accessInfo)
                    {
                        echo "<option value=".$accessInfo['resource_id'].">".$accessInfo['resource_name']."</option>";
                    }
                    echo "</SELECT>";
                    echo "<SELECT name=\"add_access_permission_id_".$resourceTypeId."\">";
                    foreach($permissionTypeList as $permissionId => $permissionName)
                    {
                        echo "<option value=".$permissionId.">".$permissionName."</option>";
                    }
                    echo "</SELECT>";
                    echo "<input type=\"submit\" name=\"add_access_".$resourceTypeId."\" value=\"Add\" class=\"btn btn-primary\">";
                    echo "</div>";
                }

                echo "<table class=\"table table-striped table-hover\"><tr>";
                foreach($permissionTypeList as $permissionId=>$permissionName)
                {
                    echo "<th>".$permissionName."</th>";
                }
                echo "<th>Resource Name</th>";
                if($selectedParticipantTypeId!=AccessControl::PARTICIPANT_ROLE)
                {
                    echo "<th>Delete</th>";
                }
                echo "</tr>";
                foreach ($accessList as $id => $accessInfo) {
                    //Show access control if participant type is Role or if access control exists for other participant types
                    if($selectedParticipantTypeId==AccessControl::PARTICIPANT_ROLE || $accessInfo['id'] > 0)
                    {
                        echo "<tr>";
                        foreach($permissionTypeList as $permissionId => $permissionName)
                        {
                            echo "<td><center><input type=\"radio\" name=\"" . $accessInfo['resource_id'] . "_" . $resourceTypeId . "_" . $selectedParticipantId . "_" . $selectedParticipantTypeId . "\" value=" . $permissionId . " " . (($accessInfo['permission'] == $permissionId) ? "checked" : "unchecked") . "></center></td>";
                        }

                        //Add checkbox in order to allow an array to be POSTed in order to be able to list resources info
                        echo "<td>" . $accessInfo['resource_name'] . "<div class=\"hidden\"><input type=\"checkbox\" name=\"resourcesInfo[]\" value=\"" . $accessInfo['resource_id'] . "_" . $resourceTypeId . "_" . $selectedParticipantId . "_" . $selectedParticipantTypeId . "\" checked></div></td>";
                        if($selectedParticipantTypeId!=AccessControl::PARTICIPANT_ROLE)
                        {
                            echo "<td><input type=\"checkbox\" name=\"deleteResourceAccess[]\" value=\"" . $accessInfo['resource_id'] . "_" . $resourceTypeId . "_" . $selectedParticipantId . "_" . $selectedParticipantTypeId . "\"></td>";
                        }
                        echo "</tr>";
                    }
                }
                echo "</table></div></div>";
            }
        }
        ?>
        </form>
    </div>
</div>