SELECT
	ad.object_name, ad.summary, af.signature
FROM
	api_function af
INNER JOIN
	api_documentation ad ON af.did = ad.did
WHERE
	ad.object_type = 'function' AND ad.object_name NOT LIKE '%::%'
ORDER BY ad.object_name
