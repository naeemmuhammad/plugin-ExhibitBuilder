<?php head(array('title'=>'Add Exhibit', 'body_class'=>'exhibits')); ?>
<?php echo js('exhibits'); ?>
<?php echo js('listsort'); ?>

<script type="text/javascript" charset="utf-8">	
//<![CDATA[

    
    
    Omeka = Omeka || new Object;
    Omeka.ExhibitForm = new Class.create({
        /**
         * Pass a set of URLs to the app so it doesn't have to hard code the PHP 
         * for building URLs into the Javascript.
         */
        initialize: function(urls, addSection) {
            this.urls = urls;
            
            this.addSectionButton = $(addSection);            
        },
        
        loadSectionForm: function(exhibit_id) {
            
            var addSectionButton = this.addSectionButton;
            var sectionFormDiv = $('new-section');
            
    		//Now submit the request for the mini-form
    		new Ajax.Updater(sectionFormDiv, this.urls.sectionForm, {
    			parameters: "id=" + exhibit_id,
    			onFailure: function(t) {
    				Omeka.flash(t.responseText);
    			},
    			onSuccess: function(t) {
    				addSectionButton.hide();	
    			},
    			onComplete: function(t) {
                    new Effect.SlideDown(sectionFormDiv,{duration:0.8});

                    // debugger;
                    // 
                    // var firstInput = sectionFormDiv.down('input').first();
                    // firstInput.scrollTo();
                    // firstInput.focus();
 
                    //Now make the add/cancel links work
                    $('add_section').observe('click', function(e) {
                        e.stop();
                        this.addSection();
                    }.bind(this));
                    $('cancel-add').observe('click', function(e) {
                        e.stop();
                        this.removeAddSectionForm();
                    }.bind(this));
    			}.bind(this)
    		});            
        },
        
        addSection: function() {
		
    		//Serialize all the form inputs (also specify JSON output)
    		var inputs = $$('#new-section input, #new-section textarea');
    		var params = Form.serializeElements(inputs) + "&output=json";
		
    		//Generate the URI for the 
		
    		var addSectionUri = this.urls.addSection + "/" + this.getExhibitId();
		
    		//Send an AJAX request that saves the Section, then send another that updates the section list
    		new Ajax.Request(addSectionUri, {
    			parameters: params,
    			onSuccess: function(t) {
    				//When successful, update the section list
    				new Ajax.Updater('section-list', this.urls.sectionList, {
    					parameters: {id: getExhibitId()},
    					onSuccess: function(t) {
    						//flash a happy message and get rid of the form
    						Omeka.flash('Section has been saved successfully!', 'success');
    						removeAddSectionForm();
    					},
    					onFailure: function(t) {
    						alert(t.responseText);
    					},
    					onComplete: function(t) {
    						//Make the section list draggable
    						makeSectionListDraggable();
    						//Highlight the section list
    						new Effect.Highlight($('section-list').parent);
    						this.addSectionButton.show();
						
    					}
    				});
    			},
    			//When adding a section does not work
    			on422: function(t, section) {
    				var error = section['Flash'];
    				alert("Error:\n\n" + error);
    				Omeka.flash(error);
				
    				//Update the section slug in case that is a cause of the error
    				$('section-slug').value = section['slug'];
    			},
    		});            
        },

    	removeAddSectionForm: function() {		
    		$('new-section').update();
    		this.addSectionButton.show();
    	},
    	
    	//This is a bit of a hack.  The exhibit ID is a hidden value on the form
    	getExhibitId: function() {
    		var id = $('exhibit_id').value;
    		return parseInt(id);
    	},
    	
    	setExhibitId: function(val) {
    		$('exhibit_id').value = val;
    	},
    	
    	//Save the exhibit and return the unique identifier of the new exhibit
    	saveNewExhibit: function()
    	{		
    		$('exhibit-form').request({
                parameters: {
                    output: "json"
                },
    			onSuccess: function(t) {                
    				debugger;
    				Omeka.flash('Exhibit was saved successfully', 'success');
    				setExhibitId(exhibit['id']);				

    				exhibit_id = exhibit['id'];

    				loadSectionForm(exhibit_id);

    				//Update the form so that it has an action corresponding to edit rather than add
    				$('exhibit-form').action = this.urls.edit + exhibit_id;		 		
    			},
    			on404: function(t) {
    			    debugger;
    //			    Omeka.flash("An error has occurred in saving the exhibit: " + t.responseText, 'error');
    			},
    			//An invalid form submission will return with a 422 response code
    			on422: function(t) {
    			    var errorMsg = t.responseJSON['flash'];
    			    Omeka.flash(errorMsg, 'error');
    			    alert(errorMsg);
    			},
    			onComplete: function(t, exhibit) {
    				//update the exhibit slug b/c that is most likely to be auto-generated
    				$('slug').value = exhibit['slug'];
    			}		    
    		});
    	},

    	clickToSaveExhibit: function() {

    		//When you click the 'add_new_section' button, add that section
    		this.addSectionButton.observe('click', function(e) {
    			e.stop();
    			
    			var exhibit_id = this.getExhibitId();
    			//If we don't have a valid exhibit ID, we need to save the exhibit first
    			if(isNaN(exhibit_id)) {				
    				this.saveNewExhibit();
    			}
    			else {
    				this.loadSectionForm(exhibit_id);
    			}			
    		}.bind(this));		
    	}   	    
    });


    var urls = {
        sectionForm: "<?php echo uri('exhibits/section-form'); ?>",
        addSection: "<?php echo url_for(array('controller'=>'exhibits','action'=>'add-section'), 'default'); ?>",
        sectionList: "<?php echo url_for(array('controller'=>'exhibits','action'=>'section-list'), 'default'); ?>",
        edit: "<?php echo uri('exhibits/edit/'); ?>"
    };
        
	Event.observe(window, 'load', function() {	
        var exhibitBuilder = new Omeka.ExhibitForm(urls, 'add_new_section');
        exhibitBuilder.clickToSaveExhibit();
		makeSectionListDraggable(exhibitBuilder);
	});
    
    function makeSectionListDraggable(exhibitBuilder)
	{	    
		var list = $('section-list');

	    var exhibit_id = exhibitBuilder.getExhibitId();	
		listSorter.list = list;
		listSorter.recordId = exhibit_id;
    	listSorter.form = $('exhibit-form');
    	listSorter.editUri = "<?php echo url_for(array('controller'=>'exhibits','action'=>'edit'),'default'); ?>/" + exhibit_id;
    	listSorter.partialUri = "<?php echo url_for(array('controller'=>'exhibits', 'action'=>'section-list')); ?>?id="+exhibit_id;
    	listSorter.tag = 'li';
    	listSorter.handle = 'handle';
    	listSorter.confirmation = 'Are you sure you want to delete this section?';
    	listSorter.deleteLinks = '.section-delete a';
    	listSorter.callback = styleExhibitBuilder;


		if(listSorter.list) {
			//Create the sortable list
			makeSortable(listSorter.list);
		}

		styleExhibitBuilder();		
	}   
	
    var listSorter = {};




//]]>	
</script>
<?php common('exhibits-nav'); ?>
<div id="primary">
	<h1>Add Exhibit</h1>

<form id="exhibit-form" method="post" class="exhibit-builder">

	<fieldset>
		<legend>Exhibit Metadata</legend>
		<?php echo flash();?>
	<div class="field">
	<?php echo text(array('name'=>'title', 'class'=>'textinput', 'id'=>'title'), $exhibit->title, 'Exhibit Title'); ?>
	<?php echo form_error('title'); ?>
	</div>
	<div class="field"><?php echo text(array('name'=>'slug', 'id'=>'slug', 'class'=>'textinput'), $exhibit->slug, 'Exhibit Slug (no spaces or special characters)'); ?>
	<?php echo form_error('slug'); ?>
	</div>
	<div class="field"><?php echo text(array('name'=>'credits', 'id'=>'credits', 'class'=>'textinput'), $exhibit->credits,'Exhibit Credits'); ?></div>
	<div class="field"><?php echo textarea(array('name'=>'description', 'id'=>'description', 'class'=>'textinput','rows'=>'10','cols'=>'40'), $exhibit->description, 'Exhibit Description'); ?></div>	
	<div class="field"><?php echo text(array('name'=>'tags', 'id'=>'tags', 'class'=>'textinput'), tag_string($exhibit,null,', ',true), 'Exhibit Tags'); ?></div>
	<div class="field">
		<label for="featured">Exhibit is featured:</label>
		<div class="radio"><?php echo radio(array('name'=>'featured', 'id'=>'featured'), array('0'=>'No','1'=>'Yes'), $exhibit->featured); ?></div>
	</div>
	
	<div class="field">
		<label for="featured">Exhibit is public:</label>
		<div class="radio"><?php echo radio(array('name'=>'public', 'id'=>'public'), array('0'=>'No','1'=>'Yes'), $exhibit->public); ?></div>
	</div>
		<div class="field">
			<label for="theme">Exhibit Theme</label>
			<div class="select"><?php echo select(array('name'=>'theme','id'=>'theme'),get_ex_themes(),$exhibit->theme); ?></div>
		</div>
		</fieldset>
	<fieldset>
		<legend>Exhibit Sections</legend>
		
		<div id="section-list-container">
			<ol id="section-list">
				<?php common('section-list', compact('exhibit'), 'exhibits'); ?>
			</ol>
			<div id="new-section-link"><a href="#" name="add_new_section" id="add_new_section">Add a Section</a></div>
			<div id="new-section"></div>
			<input type="hidden" name="exhibit_id" id="exhibit_id" value="<?php echo h($exhibit->id); ?>" />
		</div>
		</fieldset>
		<fieldset>
<p>
				<button type="submit" name="save_exhibit" id="save_exhibit" class="exhibit-button">Save and Finish</button> or 
				<a href="<?php echo uri('exhibits'); ?>" class="cancel">Cancel</a></p>
		</fieldset>
</form>		
</div>
<?php foot(); ?>