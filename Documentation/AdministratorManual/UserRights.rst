User Rights
====================

There are two types of rights in this extension, read and edit. Those are pretty self explanatory.

Read on file : file appear in the list and users can download it.

Read on folder : folder appear in the list and users can go in it.

Write on file : user can edit and delete the file.

Write on folder : user can edit and delete (only if empty) the folder itself and add files and folder into it.

If a file has no rights set, his rights will be the same as his parent folder.

.. warning ::

    If folder rights are empty, it means that everyone has the given right. Therefore, you should always have a limitation set on write permissions. Otherwise, everyone might be able to edit your documents/folders.