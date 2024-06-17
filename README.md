# Synchronization-capable copies of questions of type STACK

The STACK question type allows you to answer math questions with algebraic 
formulas. It is possible to include randomized elements in the question 
[randomized elements](https://github.com/maths/moodle-qtype_stack/blob/master/doc/en/Authoring/Deploying.md) 
into the question, so that different students receive different variants of the 
question. This is achieved by generating a list of seeds for the random function, 
which then correspond to the different variants of the question.

This plugin automatically creates a copy for each such "deployed variant" of a 
STACK question. The creation of copies is triggered when a question is created 
and when a question is updated. If the original question is deleted, all copies 
created by this plugin also will be deleted.


## Installation

Install the source into the *local/qtypestack_synccopies* directory in your moodle.

Log in as admin and select:

Settings > Site administration > Plugins > Local plugins > Manage STACK synccopies


## Version history of questions

Since Moodle 4.1, a version history is saved for questions. Unfortunately, there 
is no reasonable programming interface that could address this plugin so that 
the copies are saved analogously together with the version history. Instead, 
a separate copy is currently created for each version of a question. The version 
used is specified in the name of the copy.

## Manual triggering of the process for creating copies

Unfortunately, adding new seeds to a STACK question does not trigger the event 
that signals to the system that a question has been answered. So if you do not 
explicitly click on the "Update" button when editing the STACK question (you may 
not want to do this because this creates a new version of the question in the 
version history), the process for creating the copies is not triggered. In such 
cases, you can use a [Block](https://docs.moodle.org/401/en/Blocks) to remedy 
the situation.

A block of the type "Text" can be added on the question bank page. In the block 
settings, switch to HTML mode for text editing (see [here button 14](https://docs.moodle.org/401/en/Atto_editor#/media/File:Attobottomline.png)). 
Then enter the following HTML code (mymoodle.com should be replaced by the 
domain of the Moodle system):

    <input type="submit" class="btn btn-primary" value="Update" data-initial-value="Save changes" onclick="var xhr = new XMLHttpRequest(); xhr.open('POST', 'https://mymoodle.com/local/qtypestack_synccopies/update.php', true); xhr.send(); location.reload()">

After saving the text block, an "Update" button is displayed, which triggers 
the process of manually copying the questions.
