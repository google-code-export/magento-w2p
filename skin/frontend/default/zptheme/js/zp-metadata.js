function zp_set_metadata (field, key, value) {
  if (!key)
    zp_clear_metadata(field);
  else if (typeof key === 'object')
    field.metadata = key;
  else {
    if (!field.metadata)
      field.metadata = {};

    field.metadata[key] = value;
  }
}

function zp_get_metadata (field, key, value) {
  if (!field.metadata)
    return undefined;

  return field.metadata[key];
}

function zp_clear_metadata (field) {
  field.metadata = undefined;
}

function zp_convert_metadata_to_string (field) {
  if (!field.metadata)
    return null;

  var s = '';

  for (var key in field.metadata)
    s += key + '=' + field.metadata[key] + ';';

  return s.substring(0, s.length - 1);
}
