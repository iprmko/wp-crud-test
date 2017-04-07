jQuery(document).ready(function($) {

	// Create a crud service instance
	var crud = CRUD_Endpoint($, CRUD_PARAMS.root, 'vendor/v1/route', CRUD_PARAMS.nonce);

	// Create a table
	var table = CRUD_Table($, '#crud-items', ['id', 'name', 'created', 'updated', 'data']);

	// Registe select and check callback
	table.onRowSelected = onRowSelected;
	table.onRowChecked = onRowChecked;

	// Handle create button action
	$('#crud-create-button').on('click', function(e) {
		e.preventDefault();

		var data = getEditFormData();

		crud.create(data)
			.done(handleSuccess)
			.fail(handleError);
	});

	// Handle read button action
	$('#crud-read-button').on('click', function(e) {
		// Get optional queryParams
		var queryParams = $('#crud-query-params').val() || '';
		reloadItems(queryParams);
	});

	// Handle update button action
	$('#crud-update-button').on('click', function(e) {
		var selectedRow = table.getSelectedRow()[0];
		if (selectedRow) {
			//On update send the id and timestamp for allow conflicts detection
			var itemID = parseInt(table.findCell(selectedRow, 'id'));
			var itemUpdateTimestamp = table.findCell(selectedRow, 'updated');
			var data = getEditFormData();
			data.id = itemID;
			data.updated = itemUpdateTimestamp;
			crud.update(data)
				.done(handleSuccess)
				.fail(handleError);
		}
	});

	// Handle delete button action
	$('#crud-delete-button').on('click', function(e) {
		var selectedID = table.getCheckedRows()
			.map(function(indx, row) {
				return parseInt(table.findCell(row, 'id'));
			});

		crud.delete(selectedID)
			.done(handleSuccess)
			.fail(handleError);
	});

	// Start-up with an updated table
	reloadItems();

	// Get items from the rest endpoint then fill the table
	function reloadItems(queryParams) {
		crud.read(queryParams)
			.done(function(response) {
				table.fill(response);
			})
			.fail(handleError);
	}

	// Table selection callback
	function onRowSelected(row) {
		var selectedID = parseInt(table.findCell(row, 'id'));
		if (isNaN(selectedID)) {
			setEditFormData({});
			$('#crud-update-button').attr('disabled', 'disable');
			return;
		}
		$('#crud-update-button').removeAttr('disabled');
		crud.read(selectedID)
			.done(function(items) {
				setEditFormData(items[0]);
			})
			.fail(handleError);
	}

	// Table check callback
	function onRowChecked(row) {
		if (table.getCheckedRows().length) {
			$('#crud-delete-button').removeAttr('disabled');
		} else {
			$('#crud-delete-button').attr('disabled', 'disable');
		}
	}

	// Fill the form with model data
	function setEditFormData(data) {
		$('#item-name').val(data.name || '');
		$('#item-data').val(data.data || '');
	}
	// Get a data model from the form
	function getEditFormData() {
		return {
			name: $('#item-name').val(),
			data: $('#item-data').val(),
		};
	}

	function handleError(err) {
		console.warn(err);
		var respText = JSON.parse(err.responseText);
		alert(respText.message);
	}

	function handleSuccess(resp) {
		console.info("Success!:", resp);
		reloadItems();
	}

});

/**
 * CRUD Service hub
 * @param object $        jquery
 * @param string root     server root url
 * @param string endpoint api endpoint
 * @param string nonce    authentication nonce
 */
function CRUD_Endpoint($, root, endpoint, nonce) {
	var crud = {
		create: insertItem,
		read: getItems,
		update: insertItem,
		delete: deleteItem,
	};
	return crud;

	function getItems(queryParams) {
		var query =
			(typeof queryParams === 'number') ? root + endpoint + '/' + queryParams :
			(typeof queryParams === 'string') ? root + endpoint + '?' + queryParams :
			root + endpoint;

		return $.ajax({
			method: 'GET',
			url: query,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nonce);
			}
		});
	}

	function insertItem(data) {
		return $.ajax({
			method: 'POST',
			url: root + endpoint,
			data: data,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nonce);
			}
		});
	}

	function deleteItem(ids) {
		var promises = ids.map(function(indx, id) {
			return $.ajax({
				method: 'DELETE',
				url: root + endpoint + '/' + id,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', nonce);
				}
			});
		});
		return $.when.apply($, promises);
	}
}

/**
 * CRUD table logic
 * @param object $       jqeury instance
 * @param string target  target table selector
 * @param array  columns columns names
 */
function CRUD_Table($, target, columns) {
	var table = {
		target: target,
		columns: columns,
		create: createTable,
		fill: fillTable,
		getCheckedRows: getCheckedRows,
		getSelectedRow: getSelectedRow,
		findCell: findCell,
		onRowChecked: nop,
		onRowSelected: nop,
	};

	createTable.call(this, table);
	return table;



	function nop() {}

	function fillTable(items) {
		var cols = this.columns;
		this.$element.find('tbody')
			.empty()
			.append(
				items.map(function(item) {
					return createTableRow(cols, item);
				})
			);
		checkAllRows(this.$element.find('thead .check-all').prop('checked'));
		selectRow(null);
	}

	function createTable(table) {
		var $cols = table.columns.map(function(c) {
			return $('<td>').append(c);
		});
		var $checkAll = $('<input type="checkbox">')
			.addClass('check-all')
			.on('change', function() {
				checkAllRows($(this).prop('checked'));
			});
		var $header = $('<thead>')
			.append($('<td>').append($checkAll))
			.append($cols);
		var $body = $('<tbody>');
		var $table = $('<table>')
			.append($header)
			.append($body);
		$(table.target).append($table);
		table.$element = $table;
	}

	function checkAllRows(check) {
		table.$element
			.find('tr').find('th input')
			.prop('checked', check);
		table.onRowChecked($(this));
	}

	function getCheckedRows() {
		return table.$element.find('tbody tr')
			.filter(isRowSelected);

		function isRowSelected(indx, tr) {
			return $(tr).find('th input').prop('checked');
		}
	}

	function findCell(row, field) {
		var indx = table.columns.indexOf(field);
		var cell = $(row).find('td')[indx];
		return $(cell).html();
	}

	function createTableRow(columnNames, rowValues) {
		var cols = columnNames.map(function(c) {
			return $('<td>').append(rowValues[c]);
		});
		var $checkbox = $('<th>').append(
			$('<input type="checkbox">')
			.on('click', function(e) {
				table.onRowChecked(table.getCheckedRows());
			})
		);
		return $('<tr>')
			.append($checkbox)
			.append(cols)
			.on('click', function(e) {
				if (e.srcElement.parentElement === $(this).find('th')[0])
					return;
				selectRow($(this));
			});
	}

	function selectRow($row) {
		var deselect = (!$row) || getSelectedRow()[0] === $row[0];
		table.$element.find('tr')
			.prop('selected', false)
			.removeClass('selected');
		if (!deselect)
			$row.prop('selected', true)
			.addClass('selected');
		table.onRowSelected(table.getSelectedRow());
	}

	function getSelectedRow() {
		return table.$element.find('tr:selected').first();
	}

}
