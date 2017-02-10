Frequently Asked Questions
==========================

.. only:: html

	**Categories:**

	.. contents::
		:local:
		:depth: 1


.. _faq-users:

Setup
-----

.. question

**Can I import the files and folder that were in my filelist before the installation of the extension?**

.. answer

*Yes. You can use the update script in the extension manager. This will go through your filelist and initialize the necessary tables. After that, you should be able to choose any folder to be the root folder.
If your filelist is big, the update script might take some time. You may have to increase your max_execution_time value.
When you use the filelist in BE, the database is automatically updated. This option can also be activated in FE context via typoscript, but performances will be decreased. It is not activated by default.*


-------

.. question

**Can I have several file management plugin in one TYPO3 instance.**

.. answer

*Yes, you just have to create multiple plugins and set the root folder for each one of them.*

