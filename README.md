# BuddyPress Group Restriction

This plugin allows you to prevent group access to a group based on Member Type (introduced in BP 2.2+).

## Requirements

- BuddyPress 2.2+
- Groups Component Activated


## Setup Instructions

Once you have activated the plugin you need to create Member Types. You can do this in three ways:

### 1. Set up a new xProfile field to use for your Member Types

![screenshot2](https://cloud.githubusercontent.com/assets/855037/7179337/1283c98a-e437-11e4-9bb3-353c5cfa93b3.png)

1. Go to Users > Profile Fields and create a new field with the type "Member Type"
2. Add the Member Types you need and save the field
3. Members who fill in this field are now assigned a Member Type based on this field value.

### 2. Assigning Member Types to users based on an existing xProfile field **

1. Change the Field type of an existing field (this needs to be a Radio/Dropdown field) into a Member Type field.
2. Go to Tools > BuddyPress and select *Migrate/Reset xProfile data to member types*
3. Press Repare Items and all your members will be assigned the Member Type based on the field value.

### 3. Developers can register Member Types manually

1. Follow the guide here: https://codex.buddypress.org/developer/member-types/
2. Using this solution users are unable to change their member type themselves.

## Restricting Groups

Once you have configured the Member Types users have the ability to restrict users with **other** members types from joining groups they have created. 

![screenshot1](https://cloud.githubusercontent.com/assets/855037/7179244/0af45442-e436-11e4-8065-6385020ac5ff.png)

### Notes
- Public & Private Groups which are restricted are still shown in the Groups Directory. When a user with the wrong Member Type tries to join a restricted group they will be redirected to the Group directory and a message is shown
- Group Admins can remove the Group Restrictions after group creation if needed.
- You can write a custom explanation message for your restrictions by editing the xProfile field assigned to the Member Type on the Users > Profile Fields > *Member Type Field Edit Screen*
- Feature Request, Suggestions and Contributions welcome!
