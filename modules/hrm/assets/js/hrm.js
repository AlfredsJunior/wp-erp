/* jshint devel:true */
/* global wpErpHr */
/* global wp */

;(function($) {
    'use strict';

    var WeDevs_ERP_HR = {

        /**
         * Initialize the events
         *
         * @return {void}
         */
        initialize: function() {
            // Department
            $( '.erp-hr-depts' ).on( 'click', 'a#erp-new-dept', this.department.create );
            $( '.erp-hr-depts' ).on( 'click', 'a.submitdelete', this.department.remove );
            $( '.erp-hr-depts' ).on( 'click', 'span.edit a', this.department.edit );

            // Designation
            $( '.erp-hr-designation' ).on( 'click', 'a#erp-new-designation', this.designation.create );
            $( '.erp-hr-designation' ).on( 'click', 'a.submitdelete', this.designation.remove );
            $( '.erp-hr-designation' ).on( 'click', 'span.edit a', this.designation.edit );

            // employee
            $( '.erp-hr-employees' ).on( 'click', 'a#erp-employee-new', this.employee.create );
            $( '.erp-hr-employees' ).on( 'click', 'span.edit a', this.employee.edit );
            $( '.erp-hr-employees' ).on( 'click', 'a.submitdelete', this.employee.remove );
            $( '.erp-hr-employees' ).on( 'click', 'a#erp-empl-status', this.employee.updateJobStatus );
            $( '.erp-hr-employees' ).on( 'click', 'a#erp-empl-compensation', this.employee.updateJobStatus );

            $( 'body' ).on( 'click', 'a#erp-set-emp-photo', this.employee.setPhoto );
            $( 'body' ).on( 'click', 'a.erp-remove-photo', this.employee.removePhoto );

            // this.employee.updateCompensation();
        },

        initDateField: function() {
            $( '.erp-date-field').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        },

        department: {

            /**
             * Create new department
             *
             * @param  {event}
             */
            create: function(e) {
                e.preventDefault();

                $.erpPopup({
                    title: wpErpHr.popup.dept_title,
                    button: wpErpHr.popup.dept_submit,
                    content: wp.template('erp-new-dept')(),
                    extraClass: 'smaller',
                    onSubmit: function(modal) {
                        wp.ajax.send( {
                            data: this.serialize(),
                            success: function(response) {
                                var row   = wp.template('erp-dept-row'),
                                    table = $('table.department-list-table');

                                if ( table ) {
                                    var cls = ( $('tr:last', table).attr('class') === 'even' ) ? 'alternate' : 'even';

                                    response.cls = cls;
                                    table.append( row(response) );

                                    modal.closeModal();
                                }
                            },
                            error: function(error) {
                                alert( error );
                            }
                        });
                    }
                }); //popup
            },

            /**
             * Edit a department in popup
             *
             * @param  {event}
             */
            edit: function(e) {
                e.preventDefault();

                var self = $(this);

                $.erpPopup({
                    title: wpErpHr.popup.dept_update,
                    button: wpErpHr.popup.dept_update,
                    content: wp.template('erp-new-dept')(),
                    extraClass: 'smaller',
                    onReady: function() {
                        var modal = this;

                        $( 'header', modal).after( $('<div class="loader"></div>').show() );

                        wp.ajax.send( 'erp-hr-get-dept', {
                            data: {
                                id: self.data('id'),
                                _wpnonce: wpErpHr.nonce
                            },
                            success: function(response) {
                                $( '.loader', modal).remove();

                                $('#dept-title', modal).val( response.name );
                                $('#dept-desc', modal).val( response.data.description );
                                $('#dept-parent', modal).val( response.data.parent );
                                $('#dept-id', modal).val( response.id );
                                $('#dept-action', modal).val( 'erp-hr-update-dept' );

                                // disable current one
                                $('#dept-parent option[value="' + self.data('id') + '"]', modal).attr( 'disabled', 'disabled' );
                            }
                        });
                    },
                    onSubmit: function(modal) {
                        wp.ajax.send( {
                            data: this.serialize(),
                            success: function(response) {
                                var row   = wp.template('erp-dept-row');

                                response.cls = self.closest('tr').attr('class');
                                self.closest('tr').replaceWith( row(response) );

                                modal.closeModal();
                            },
                            error: function(error) {
                                alert( error );
                            }
                        });
                    }
                });
            },

            /**
             * Delete a department
             *
             * @param  {event}
             */
            remove: function(e) {
                e.preventDefault();

                var self = $(this);

                if ( confirm( wpErpHr.delConfirmDept ) ) {
                    wp.ajax.send( 'erp-hr-del-dept', {
                        data: {
                            '_wpnonce': wpErpHr.nonce,
                            id: self.data( 'id' )
                        },
                        success: function() {
                            self.closest('tr').fadeOut( 'fast', function() {
                                $(this).remove();
                            });
                        },
                        error: function(response) {
                            alert( response );
                        }
                    });
                }
            },

        },

        designation: {

            create: function(e) {
                e.preventDefault();

                $.erpPopup({
                    title: wpErpHr.popup.desig_title,
                    button: wpErpHr.popup.desig_submit,
                    content: wp.template( 'erp-new-desig' )(),
                    extraClass: 'smaller',
                    onSubmit: function(modal) {
                        wp.ajax.send( {
                            data: this.serialize(),
                            success: function(response) {
                                var row   = wp.template('erp-desig-row'),
                                    table = $('table.designation-list-table');

                                if ( table ) {
                                    var cls = ( $('tr:last', table).attr('class') === 'even' ) ? 'alternate' : 'even';

                                    response.cls = cls;
                                    table.append( row(response) );

                                    modal.closeModal();
                                }
                            },
                            error: function(error) {
                                alert( error );
                            }
                        });
                    }
                });
            },

            /**
             * Edit a department in popup
             *
             * @param  {event}
             */
            edit: function(e) {
                e.preventDefault();

                var self = $(this);

                $.erpPopup({
                    title: wpErpHr.popup.desig_update,
                    button: wpErpHr.popup.desig_update,
                    content: wp.template( 'erp-new-desig' )(),
                    extraClass: 'smaller',
                    onReady: function() {
                        var modal = this;

                        $( 'header', modal).after( $('<div class="loader"></div>').show() );

                        wp.ajax.send( 'erp-hr-get-desig', {
                            data: {
                                id: self.data('id'),
                                _wpnonce: wpErpHr.nonce
                            },
                            success: function(response) {
                                $( '.loader', modal).remove();

                                $('#desig-title', modal).val( response.name );
                                $('#desig-desc', modal).val( response.data.description );
                                $('#desig-id', modal).val( response.id );
                                $('#desig-action', modal).val( 'erp-hr-update-desig' );
                            }
                        });
                    },
                    onSubmit: function(modal) {
                        wp.ajax.send( {
                            data: this.serialize(),
                            success: function(response) {
                                var row   = wp.template('erp-desig-row');

                                response.cls = self.closest('tr').attr('class');
                                self.closest('tr').replaceWith( row(response) );

                                modal.closeModal();
                            },
                            error: function(error) {
                                alert( error );
                            }
                        });
                    }
                });
            },

            /**
             * Delete a department
             *
             * @param  {event}
             */
            remove: function(e) {
                e.preventDefault();

                var self = $(this);

                if ( confirm( wpErpHr.delConfirmDept ) ) {
                    wp.ajax.send( 'erp-hr-del-desig', {
                        data: {
                            '_wpnonce': wpErpHr.nonce,
                            id: self.data( 'id' )
                        },
                        success: function() {
                            self.closest('tr').fadeOut( 'fast', function() {
                                $(this).remove();
                            });
                        },
                        error: function(response) {
                            alert( response );
                        }
                    });
                }
            },
        },

        employee: {

            /**
             * Set photo popup
             *
             * @param {event}
             */
            setPhoto: function(e) {
                e.preventDefault();
                e.stopPropagation();

                var frame;

                if ( frame ) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: wpErpHr.emp_upload_photo,
                    button: { text: wpErpHr.emp_set_photo }
                });

                frame.on('select', function() {
                    var selection = frame.state().get('selection');

                    selection.map( function( attachment ) {
                        attachment = attachment.toJSON();

                        var html = '<img src="' + attachment.url + '" alt="" />';
                            html += '<input type="hidden" id="emp-photo-id" name="photo_id" value="' + attachment.id + '" />';
                            html += '<a href="#" class="erp-remove-photo">&times;</a>';

                        $( '.photo-container', '.erp-employee-form' ).html( html );
                    });
                });

                frame.open();
            },

            /**
             * Remove an employees avatar
             *
             * @param  {event}
             */
            removePhoto: function(e) {
                e.preventDefault();

                var html = '<a href="#" id="erp-set-emp-photo" class="button button-small">' + wpErpHr.emp_upload_photo + '</a>';
                    html += '<input type="hidden" name="photo_id" id="emp-photo-id" value="0">';

                $( '.photo-container', '.erp-employee-form' ).html( html );
            },

            /**
             * Create a new employee modal
             *
             * @param  {event}
             */
            create: function(e) {
                if ( typeof e !== 'undefined' ) {
                    e.preventDefault();
                }

                if ( typeof wpErpHr.employee_empty === 'undefined' ) {
                    return;
                }

                $.erpPopup({
                    title: wpErpHr.popup.employee_title,
                    button: wpErpHr.popup.employee_create,
                    content: wp.template('erp-new-employee')( wpErpHr.employee_empty ),

                    onReady: function() {
                        $( '.erp-date-field').datepicker({
                            dateFormat: 'yy-mm-dd',
                            changeMonth: true,
                            changeYear: true
                        });
                    },

                    /**
                     * Handle the onsubmit function
                     *
                     * @param  {modal}
                     */
                    onSubmit: function(modal) {
                        $( 'button[type=submit]', '.erp-modal' ).attr( 'disabled', 'disabled' );

                        wp.ajax.send( 'erp-hr-employee-new', {
                            data: this.serialize(),
                            success: function(response) {

                                var row = wp.template( 'erp-employee-row');
                                response.class = 'hello';

                                $( 'table.erp-employee-list-table' ).append( row(response) );

                                modal.closeModal();
                            },
                            error: function(error) {
                                modal.enableButton();
                                alert( error );
                            }
                        });
                    }
                });
            },

            /**
             * Edit an employee
             *
             * @param  {event}
             */
            edit: function(e) {
                e.preventDefault();

                var self = $(this);

                $.erpPopup({
                    title: wpErpHr.popup.employee_update,
                    button: wpErpHr.popup.employee_update,
                    onReady: function() {
                        var modal = this;

                        $( 'header', modal).after( $('<div class="loader"></div>').show() );

                        wp.ajax.send( 'erp-hr-emp-get', {
                            data: {
                                id: self.data('id'),
                                _wpnonce: wpErpHr.nonce
                            },
                            success: function(response) {
                                var html = wp.template('erp-new-employee')( response );
                                $( '.content', modal ).html( html );
                                $( '.loader', modal).remove();

                                $( '.erp-date-field').datepicker({
                                    dateFormat: 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear: true
                                });

                                $( 'li[data-selected]', modal ).each(function() {
                                    var self = $(this),
                                        selected = self.data('selected');

                                    if ( selected !== '' ) {
                                        self.find( 'select' ).val( selected );
                                    }
                                });

                                // disable current one
                                $('#work_reporting_to option[value="' + response.id + '"]', modal).attr( 'disabled', 'disabled' );
                            }
                        });
                    },
                    onSubmit: function(modal) {
                        modal.disableButton();

                        wp.ajax.send( {
                            data: this.serialize(),
                            success: function(response) {
                                var single = ( self.data( 'single' ) === true ) ? true : false;

                                if ( single ) {
                                    $( '.erp-area-left' ).load( window.location.href + ' #erp-area-left-inner' );
                                } else {
                                    var row = wp.template( 'erp-employee-row');

                                    response.class = self.closest('tr').attr('class');
                                    self.closest('tr').replaceWith( row(response) );
                                }

                                modal.closeModal();
                            },
                            error: function(error) {
                                alert( error );
                            }
                        });
                    }
                });
            },

            /**
             * Remove an employee
             *
             * @param  {event}
             */
            remove: function(e) {
                e.preventDefault();

                var self = $(this);

                if ( confirm( wpErpHr.delConfirmEmployee ) ) {
                    wp.ajax.send( 'erp-hr-emp-delete', {
                        data: {
                            '_wpnonce': wpErpHr.nonce,
                            id: self.data( 'id' )
                        },
                        success: function() {
                            self.closest('tr').fadeOut( 'fast', function() {
                                $(this).remove();
                            });
                        },
                        error: function(response) {
                            alert( response );
                        }
                    });
                }
            },

            updateJobStatus: function(e) {
                if ( typeof e !== 'undefined' ) {
                    e.preventDefault();
                }

                var self = $(this);

                $.erpPopup({
                    title: self.data('title'),
                    button: wpErpHr.popup.update_status,
                    content: wp.template( self.data('template') )({ id: self.data('id') }),
                    extraClass: 'smaller',
                    onReady: WeDevs_ERP_HR.initDateField,
                    onSubmit: function(modal) {
                        wp.ajax.send( {
                            data: this.serializeObject(),
                            success: function() {
                                $( '.erp-area-left' ).load( window.location.href + ' #erp-area-left-inner' );
                                modal.closeModal();
                            },
                            error: function(error) {
                                modal.enableButton();
                                alert( error );
                            }
                        });
                    }
                });
            },
        }

    };

    $(function() {
        WeDevs_ERP_HR.initialize();
    });
})(jQuery);