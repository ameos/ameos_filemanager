Installation
==========================

ameos_filemanager needs a little more than a simple installation.

Typoscript inclusion
----------------------

ameos_filemanager uses typoscript. You need to include the typosript to your website. For that, in "Template" mode, click on your root page, then click on "Edit the whole template record" and go into "include" tab.

There, you need to add Ameos file manager into the "Include static (from extensions)" list.

Initialization
----------------------

You must launch the update script in order to iniate the database.
This will parse the folders in your filelist and make the necessary adjustement in the database. (create folders, link sys_file to folders).

.. warning ::

    If your filelist is big and your max_execution_time low. The update script might not have time to process the entire filelist.

Once the initiation is over, you can place the plugin "Frontend File Manager" in any page of your website. ( See below for configuration ).

General configuration
----------------------

This is the basic configuration you need to make your extension to work.

+----------------------------------------------------+---------------------------------------------+
|                       Option                       |                     Detail                  |
+====================================================+=============================================+
|                  User groups folder                | Folder where you users are stored.          |
+----------------------------------------------------+---------------------------------------------+
|                       Storage                      | Storage mount where your files are.         |
+----------------------------------------------------+---------------------------------------------+
|                     Root folder                    | Root folder of your GED.                    |
+----------------------------------------------------+---------------------------------------------+


Tab display
----------------------

These options are for display purpose only, you may let the default settings or choose your own :

+----------------------------------------------------+----------------------------------------------+
|                       Option                       |                     Detail                   |
+====================================================+==============================================+
|                                                    | These will be the headers of the tab,        |
|         Columns that'll be in the header tab       | you can choose which one you want to display |
|                                                    | and their order.                             |
+----------------------------------------------------+----------------------------------------------+
|    Allowed actions (if displayed in tab header)    | If action was selected in the displayed      |
|                                                    | columns, you can choose which one will       |
|                                                    | appear in the selected box.                  |
|                                                    |                                              |
|                                                    | Note that if not selected, download will     |
|                                                    | still be available via links on the file     |
|                                                    | title and icon.                              |
+----------------------------------------------------+----------------------------------------------+
|Path to the folder where extension icons are stored.| With this option, you can change the images  |
|                                                    | used in the icon column.                     |
|                                                    |                                              |
|                                                    | Images must be nammed acording to the        |
|                                                    | following pattern :                          |
|                                                    | *icon_[fileextention].png*                   |
|                                                    |                                              |
|                                                    | For videos files for exemple, it will be     |
|                                                    | *icon_avi.png*, *icon_mkv.png* ...           |
|                                                    |                                              |
|                                                    | Please be sure to have an image named        |
|                                                    | *icon_default_file.png* for the default file |
|                                                    | icon. One named *icon_folder.png* for the    |
|                                                    | folder icon, and one named                   |
|                                                    | *icon_previous_folder.png* for the previous  |
|                                                    | folder icon.                                 |
+----------------------------------------------------+----------------------------------------------+

Edit options
----------------------

These are the options used to build the frontend form to add/edit files and folders

+--------------------------------------------------------+---------------------------------------------+
|                       Option                           |                     Detail                  |
+========================================================+=============================================+
| Groups that can be selected while editing a record     | List of selectionable groups in FE forms.   |
|                                                        | You can use this option to prevent FE admin |
|                                                        | to give rights to any group.                |
+--------------------------------------------------------+---------------------------------------------+
| Categories that can be selected while editing a record | List of selectionable groups in FE forms.   |
|                                                        | You can use this option to prevent FE admin |
|                                                        | to give rights to any group.                |
+--------------------------------------------------------+---------------------------------------------+