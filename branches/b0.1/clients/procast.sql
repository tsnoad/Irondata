--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: autotable_constraints; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE autotable_constraints (
    autotable_constraints_id integer NOT NULL,
    template_id integer,
    column_id integer,
    "type" text,
    value text,
    choose boolean DEFAULT false
);


ALTER TABLE public.autotable_constraints OWNER TO evan;

--
-- Name: autotable_constraints_autotable_constraints_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE autotable_constraints_autotable_constraints_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.autotable_constraints_autotable_constraints_id_seq OWNER TO evan;

--
-- Name: autotable_constraints_autotable_constraints_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE autotable_constraints_autotable_constraints_id_seq OWNED BY autotable_constraints.autotable_constraints_id;


--
-- Name: autotable_constraints_autotable_constraints_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('autotable_constraints_autotable_constraints_id_seq', 41, true);


--
-- Name: autotable_templates; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE autotable_templates (
    autotable_template_id integer NOT NULL,
    template_id integer,
    column_id integer,
    duplicates boolean,
    subtotal boolean,
    sort text,
    "aggregate" text,
    label text,
    optional boolean,
    axis text
);


ALTER TABLE public.autotable_templates OWNER TO evan;

--
-- Name: autotable_templates_autotable_template_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE autotable_templates_autotable_template_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.autotable_templates_autotable_template_id_seq OWNER TO evan;

--
-- Name: autotable_templates_autotable_template_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE autotable_templates_autotable_template_id_seq OWNED BY autotable_templates.autotable_template_id;


--
-- Name: autotable_templates_autotable_template_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('autotable_templates_autotable_template_id_seq', 209, true);


--
-- Name: columns; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE columns (
    column_id integer NOT NULL,
    name text,
    table_id integer,
    human_name text,
    description text,
    data_type text,
    key_type text,
    references_column integer,
    example text,
    available boolean DEFAULT false,
    records integer,
    modified_by text,
    modified_time timestamp without time zone DEFAULT now()
);


ALTER TABLE public.columns OWNER TO evan;

--
-- Name: columns_column_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE columns_column_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.columns_column_id_seq OWNER TO evan;

--
-- Name: columns_column_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE columns_column_id_seq OWNED BY columns.column_id;


--
-- Name: columns_column_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('columns_column_id_seq', 268, true);


--
-- Name: databases; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE databases (
    database_id integer NOT NULL,
    name text,
    object_id integer,
    host text,
    username text,
    "password" text,
    human_name text,
    description text,
    notes text,
    records integer,
    modified_by text,
    modified_time timestamp without time zone DEFAULT now()
);


ALTER TABLE public.databases OWNER TO evan;

--
-- Name: databases_database_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE databases_database_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.databases_database_id_seq OWNER TO evan;

--
-- Name: databases_database_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE databases_database_id_seq OWNED BY databases.database_id;


--
-- Name: databases_database_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('databases_database_id_seq', 1, true);


--
-- Name: foo; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE foo (
);


ALTER TABLE public.foo OWNER TO evan;

--
-- Name: list_constraints; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE list_constraints (
    list_constraints_id integer NOT NULL,
    template_id integer,
    column_id integer,
    "type" text,
    value text,
    choose boolean DEFAULT false
);


ALTER TABLE public.list_constraints OWNER TO evan;

--
-- Name: list_constraints_list_constraints_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE list_constraints_list_constraints_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.list_constraints_list_constraints_id_seq OWNER TO evan;

--
-- Name: list_constraints_list_constraints_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE list_constraints_list_constraints_id_seq OWNED BY list_constraints.list_constraints_id;


--
-- Name: list_constraints_list_constraints_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('list_constraints_list_constraints_id_seq', 69, true);


--
-- Name: list_templates; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE list_templates (
    list_template_id integer NOT NULL,
    template_id integer,
    column_id integer,
    duplicates boolean,
    subtotal boolean,
    sort text,
    "aggregate" text,
    label text,
    optional boolean,
    col_order integer
);


ALTER TABLE public.list_templates OWNER TO evan;

--
-- Name: list_templates_list_template_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE list_templates_list_template_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.list_templates_list_template_id_seq OWNER TO evan;

--
-- Name: list_templates_list_template_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE list_templates_list_template_id_seq OWNED BY list_templates.list_template_id;


--
-- Name: list_templates_list_template_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('list_templates_list_template_id_seq', 461, true);


--
-- Name: modules; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE modules (
    module_id text NOT NULL,
    name text NOT NULL,
    description text NOT NULL,
    "type" text NOT NULL,
    subtype text,
    status text DEFAULT 'inactive'::text NOT NULL
);


ALTER TABLE public.modules OWNER TO evan;

--
-- Name: objects; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE objects (
    object_id integer NOT NULL,
    name text,
    "type" text,
    module_id text,
    modified_by text,
    modified_time timestamp without time zone DEFAULT now()
);


ALTER TABLE public.objects OWNER TO evan;

--
-- Name: objects_object_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE objects_object_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.objects_object_id_seq OWNER TO evan;

--
-- Name: objects_object_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE objects_object_id_seq OWNED BY objects.object_id;


--
-- Name: objects_object_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('objects_object_id_seq', 1, true);


--
-- Name: people; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE people (
    person_id text NOT NULL,
    given_names text,
    family_name text,
    "password" text,
    "session" integer,
    session_time timestamp without time zone,
    modified_by text,
    modified_time timestamp without time zone DEFAULT now()
);


ALTER TABLE public.people OWNER TO evan;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE permissions (
    permission_id integer NOT NULL,
    element text,
    "function" text,
    permission boolean,
    person_id text,
    modified_by text,
    modified_time timestamp without time zone DEFAULT now()
);


ALTER TABLE public.permissions OWNER TO evan;

--
-- Name: permissions_permission_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE permissions_permission_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.permissions_permission_id_seq OWNER TO evan;

--
-- Name: permissions_permission_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE permissions_permission_id_seq OWNED BY permissions.permission_id;


--
-- Name: permissions_permission_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('permissions_permission_id_seq', 1, false);


--
-- Name: simplegraph_constraints; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE simplegraph_constraints (
    autotable_constraints_id integer NOT NULL,
    template_id integer,
    column_id integer,
    "type" text,
    value text,
    choose boolean DEFAULT false
);


ALTER TABLE public.simplegraph_constraints OWNER TO evan;

--
-- Name: simplegraph_constraints_autotable_constraints_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE simplegraph_constraints_autotable_constraints_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.simplegraph_constraints_autotable_constraints_id_seq OWNER TO evan;

--
-- Name: simplegraph_constraints_autotable_constraints_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE simplegraph_constraints_autotable_constraints_id_seq OWNED BY simplegraph_constraints.autotable_constraints_id;


--
-- Name: simplegraph_constraints_autotable_constraints_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('simplegraph_constraints_autotable_constraints_id_seq', 1, false);


--
-- Name: simplegraph_templates; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE simplegraph_templates (
    autotable_template_id integer NOT NULL,
    template_id integer,
    column_id integer,
    sort text,
    "aggregate" text,
    axis text
);


ALTER TABLE public.simplegraph_templates OWNER TO evan;

--
-- Name: simplegraph_templates_autotable_template_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE simplegraph_templates_autotable_template_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.simplegraph_templates_autotable_template_id_seq OWNER TO evan;

--
-- Name: simplegraph_templates_autotable_template_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE simplegraph_templates_autotable_template_id_seq OWNED BY simplegraph_templates.autotable_template_id;


--
-- Name: simplegraph_templates_autotable_template_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('simplegraph_templates_autotable_template_id_seq', 39, true);


--
-- Name: table_joins; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE table_joins (
    table_join_id integer NOT NULL,
    table1 integer,
    table2 integer,
    method text
);


ALTER TABLE public.table_joins OWNER TO evan;

--
-- Name: table_joins_table_join_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE table_joins_table_join_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.table_joins_table_join_id_seq OWNER TO evan;

--
-- Name: table_joins_table_join_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE table_joins_table_join_id_seq OWNED BY table_joins.table_join_id;


--
-- Name: table_joins_table_join_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('table_joins_table_join_id_seq', 8, true);


--
-- Name: tables; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE tables (
    table_id integer NOT NULL,
    name text,
    database_id integer,
    human_name text,
    description text,
    notes text,
    records integer,
    modified_by text,
    modified_time timestamp without time zone DEFAULT now()
);


ALTER TABLE public.tables OWNER TO evan;

--
-- Name: tables_table_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE tables_table_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.tables_table_id_seq OWNER TO evan;

--
-- Name: tables_table_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE tables_table_id_seq OWNED BY tables.table_id;


--
-- Name: tables_table_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('tables_table_id_seq', 14, true);


--
-- Name: templates; Type: TABLE; Schema: public; Owner: evan; Tablespace: 
--

CREATE TABLE templates (
    template_id integer NOT NULL,
    name text,
    draft boolean DEFAULT true,
    module text,
    object_id integer
);


ALTER TABLE public.templates OWNER TO evan;

--
-- Name: templates_template_id_seq; Type: SEQUENCE; Schema: public; Owner: evan
--

CREATE SEQUENCE templates_template_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.templates_template_id_seq OWNER TO evan;

--
-- Name: templates_template_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: evan
--

ALTER SEQUENCE templates_template_id_seq OWNED BY templates.template_id;


--
-- Name: templates_template_id_seq; Type: SEQUENCE SET; Schema: public; Owner: evan
--

SELECT pg_catalog.setval('templates_template_id_seq', 6, true);


--
-- Name: autotable_constraints_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE autotable_constraints ALTER COLUMN autotable_constraints_id SET DEFAULT nextval('autotable_constraints_autotable_constraints_id_seq'::regclass);


--
-- Name: autotable_template_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE autotable_templates ALTER COLUMN autotable_template_id SET DEFAULT nextval('autotable_templates_autotable_template_id_seq'::regclass);


--
-- Name: column_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE columns ALTER COLUMN column_id SET DEFAULT nextval('columns_column_id_seq'::regclass);


--
-- Name: database_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE databases ALTER COLUMN database_id SET DEFAULT nextval('databases_database_id_seq'::regclass);


--
-- Name: list_constraints_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE list_constraints ALTER COLUMN list_constraints_id SET DEFAULT nextval('list_constraints_list_constraints_id_seq'::regclass);


--
-- Name: list_template_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE list_templates ALTER COLUMN list_template_id SET DEFAULT nextval('list_templates_list_template_id_seq'::regclass);


--
-- Name: object_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE objects ALTER COLUMN object_id SET DEFAULT nextval('objects_object_id_seq'::regclass);


--
-- Name: permission_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE permissions ALTER COLUMN permission_id SET DEFAULT nextval('permissions_permission_id_seq'::regclass);


--
-- Name: autotable_constraints_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE simplegraph_constraints ALTER COLUMN autotable_constraints_id SET DEFAULT nextval('simplegraph_constraints_autotable_constraints_id_seq'::regclass);


--
-- Name: autotable_template_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE simplegraph_templates ALTER COLUMN autotable_template_id SET DEFAULT nextval('simplegraph_templates_autotable_template_id_seq'::regclass);


--
-- Name: table_join_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE table_joins ALTER COLUMN table_join_id SET DEFAULT nextval('table_joins_table_join_id_seq'::regclass);


--
-- Name: table_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE tables ALTER COLUMN table_id SET DEFAULT nextval('tables_table_id_seq'::regclass);


--
-- Name: template_id; Type: DEFAULT; Schema: public; Owner: evan
--

ALTER TABLE templates ALTER COLUMN template_id SET DEFAULT nextval('templates_template_id_seq'::regclass);


--
-- Data for Name: autotable_constraints; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY autotable_constraints (autotable_constraints_id, template_id, column_id, "type", value, choose) FROM stdin;
28	3	1	gt	2008-06-30	f
32	4	1	gt	2008-06-30	f
41	5	1	gt	2008-06-30	f
\.


--
-- Data for Name: autotable_templates; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY autotable_templates (autotable_template_id, template_id, column_id, duplicates, subtotal, sort, "aggregate", label, optional, axis) FROM stdin;
168	3	150	f	f	ASC	\N	\N	f	X
169	3	1	f	f	ASC	\N	\N	f	Y
170	3	188	f	f	\N	count	\N	f	C
195	4	150	f	f	ASC	\N	\N	f	X
196	4	1	f	f	ASC	\N	\N	f	Y
197	4	152	f	f	\N	sum	\N	f	C
207	5	150	f	f	ASC	\N	\N	f	X
208	5	1	f	f	ASC	\N	\N	f	Y
209	5	191	f	f	\N	sum	\N	f	C
\.


--
-- Data for Name: columns; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY columns (column_id, name, table_id, human_name, description, data_type, key_type, references_column, example, available, records, modified_by, modified_time) FROM stdin;
2	amount_exc_gst	2	amount_exc_gst	\N	double	PK	\N	0.17000,0.18000	f	\N	\N	2008-08-06 16:16:52.886671
3	amount_inc_gst	2	amount_inc_gst	\N	double	PK	\N	0.19000,0.20000	f	\N	\N	2008-08-06 16:16:52.894527
4	bill_reference_id	2	bill_reference_id	\N	varchar	PK	\N	A5508778416	f	\N	\N	2008-08-06 16:16:52.918195
5	bill_tran_desc	2	bill_tran_desc	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:52.926165
6	calling_number	2	calling_number	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:52.933617
7	calling_number_formatted	2	calling_number_formatted	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:52.943204
8	call_category_desc	2	call_category_desc	\N	varchar	PK	\N	National Calls	f	\N	\N	2008-08-06 16:16:52.951056
9	call_date	2	call_date	\N	datetime	PK	\N	2007-09-11 15:55:41,2007-09-11 15:56:11	f	\N	\N	2008-08-06 16:16:52.958699
10	call_destination	2	call_destination	\N	varchar	PK	\N	Toow'ba CBD	f	\N	\N	2008-08-06 16:16:52.966979
11	call_meter_units	2	call_meter_units	\N	int	PK	\N	1	f	\N	\N	2008-08-06 16:16:52.976175
12	call_type_desc	2	call_type_desc	\N	varchar	PK	\N	National Direct Dialled calls	f	\N	\N	2008-08-06 16:16:52.984753
13	cug_identifier	2	cug_identifier	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:52.992228
14	duration	2	duration	\N	int	PK	\N	6,5	f	\N	\N	2008-08-06 16:16:52.99982
15	end_date	2	end_date	\N	datetime	PK	\N	2007-09-11 00:00:00	f	\N	\N	2008-08-06 16:16:53.00916
16	excess_sent_usage	2	excess_sent_usage	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.017465
17	gnrc_cache_recvd	2	gnrc_cache_recvd	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.028754
18	gnrc_mbytes_recvd	2	gnrc_mbytes_recvd	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.036423
19	gnrc_mbytes_sent	2	gnrc_mbytes_sent	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.04613
20	gnrc_percent_util	2	gnrc_percent_util	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.053849
21	gnrc_speed	2	gnrc_speed	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.061604
22	gst_amount	2	gst_amount	\N	double	PK	\N	0.02000	f	\N	\N	2008-08-06 16:16:53.069188
23	ID	2	ID	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:16:53.074313
24	item_number	2	item_number	\N	int	PK	\N	7172,7173	f	\N	\N	2008-08-06 16:16:53.084298
25	number_called	2	number_called	\N	int	PK	\N	46464356,46464381,46464611,46464612,46464613,46464614,46464646,46464831,46464832,46464833	f	\N	\N	2008-08-06 16:16:53.09201
26	number_called_formatted	2	number_called_formatted	\N	int	PK	\N	46464356	f	\N	\N	2008-08-06 16:16:53.097235
27	provider	2	provider	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.104877
28	rate	2	rate	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.112286
29	recvd_allowance_mbytes	2	recvd_allowance_mbytes	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.12138
30	recvd_usage_mbytes	2	recvd_usage_mbytes	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.12914
31	service_location_1	2	service_location_1	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.136759
32	service_location_2	2	service_location_2	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.144344
33	service_location_3	2	service_location_3	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.151888
34	service_location_4	2	service_location_4	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.16162
35	service_number	2	service_number	\N	int	PK	\N	400362736,400362737,400362738,400362739,400362740	f	\N	\N	2008-08-06 16:16:53.169058
36	service_number_label	2	service_number_label	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.176431
37	service_type_desc	2	service_type_desc	\N	varchar	PK	\N	MobileNet	f	\N	\N	2008-08-06 16:16:53.184083
38	speed_usage_bps	2	speed_usage_bps	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.193758
39	time_amount	2	time_amount	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.201307
40	volume	2	volume	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.208841
41	volume_amount	2	volume_amount	\N	varchar	PK	\N	\N	f	\N	\N	2008-08-06 16:16:53.216331
42	chloride	3	chloride	\N	double	PK	\N	0.12300,,2423.00000,2390.00000,2382.00000,2373.00000,2361.00000,2348.00000,2346.00000,2328.00000	f	\N	\N	2008-08-06 16:16:53.25065
43	comment	3	comment	\N	varchar	PK	\N	Access format data,,Ace 30,740,	f	\N	\N	2008-08-06 16:16:53.264948
44	d1	3	d1	\N	double	PK	\N	32.10000,25.60000,25.10000,2.30000,3.80000,6.10000,,13.80000,4.90000,8.00000	f	\N	\N	2008-08-06 16:16:53.272841
45	d14	3	d14	\N	double	PK	\N	32.10000,,39.30000,63.90000,52.90000,54.10000,50.30000,48.40000,44.60000,40.70000	f	\N	\N	2008-08-06 16:16:53.281079
46	d2	3	d2	\N	double	PK	\N	32.10000,,35.90000,39.00000,24.20000,36.90000,30.20000,19.40000,26.80000,33.90000	f	\N	\N	2008-08-06 16:16:53.287494
47	d28mean	3	d28mean	\N	double	PK	\N	32.10000,70.30000,75.10000,21.80000,26.20000,39.60000,33.10000,71.60000,48.10000,61.00000	f	\N	\N	2008-08-06 16:16:53.293797
48	d28_1	3	d28_1	\N	double	PK	\N	32.10000,65.20000,74.10000,21.70000,26.20000,39.10000,33.40000,72.40000,47.70000,60.30000	f	\N	\N	2008-08-06 16:16:53.300026
49	d28_2	3	d28_2	\N	double	PK	\N	32.10000,70.30000,76.10000,21.90000,26.20000,40.10000,32.70000,70.70000,48.40000,61.60000	f	\N	\N	2008-08-06 16:16:53.30802
50	d3	3	d3	\N	double	PK	\N	32.10000,,17.30000,42.20000,39.60000,28.90000,40.00000,48.50000,21.90000,23.40000	f	\N	\N	2008-08-06 16:16:53.314354
51	d4	3	d4	\N	double	PK	\N	32.10000,,29.90000,28.60000,30.60000,31.80000,31.00000,28.50000,29.30000,29.50000	f	\N	\N	2008-08-06 16:16:53.321967
52	d5	3	d5	\N	double	PK	\N	32.10000,,32.20000,36.30000,28.70000,37.60000,31.70000,32.30000,35.20000,34.70000	f	\N	\N	2008-08-06 16:16:53.33046
53	d7	3	d7	\N	double	PK	\N	32.10000,54.10000,55.40000,14.80000,18.00000,29.30000,22.30000,35.80000,48.70000,31.20000	f	\N	\N	2008-08-06 16:16:53.336514
54	ddn	3	ddn	\N	int	PK	\N	1,2,5,4,6,10,11,9,7,8	f	\N	\N	2008-08-06 16:16:53.344594
55	density1	3	density1	\N	int	PK	\N	2001,2469,2467,2403,2408,2410,2419,2484,2433,2456	f	\N	\N	2008-08-06 16:16:53.35093
56	density2	3	density2	\N	int	PK	\N	2001,,2434,2446,2424,2427,2447,2432,2423,2437	f	\N	\N	2008-08-06 16:16:53.35896
57	density3	3	density3	\N	int	PK	\N	2001,,2430,2445,2420,2429,2424,2433,2427,2432	f	\N	\N	2008-08-06 16:16:53.366657
58	density4	3	density4	\N	int	PK	\N	2001,,2430,2450,2429,2423,2422,2456,2442,2424	f	\N	\N	2008-08-06 16:16:53.376502
59	density5	3	density5	\N	int	PK	\N	2001,,2429,2380,2393,2395,2427,2359,2419,2357	f	\N	\N	2008-08-06 16:16:53.384928
60	density6	3	density6	\N	int	PK	\N	2001,,2432,2383,2375,2434,2374,2365,2364,2337	f	\N	\N	2008-08-06 16:16:53.392983
61	densityave	3	densityave	\N	int	PK	\N	2001,2469,2467,2403,2408,2410,2419,2484,2433,2456	f	\N	\N	2008-08-06 16:16:53.399032
62	dryshrink	3	dryshrink	\N	int	PK	\N	501,,560,620,700,750,670,740,22,660	f	\N	\N	2008-08-06 16:16:53.407418
63	ID	3	ID	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:16:53.413897
64	maxagg	3	maxagg	\N	int	PK	\N	20,10,14,17	f	\N	\N	2008-08-06 16:16:53.426341
65	mixcode	3	mixcode	\N	varchar	PK	\N	beams,EZY,Ezymix,PRO-1,PRO-10,PRO-100,PRO-101,PRO-11,PRO-2,PRO-20	f	\N	\N	2008-08-06 16:16:53.433844
66	mixdesc	3	mixdesc	\N	varchar	PK	\N	Std Panel Mix,N Grade,10% flyash,Charcoal,No flyash,Black,Boral Coconut,White,Emerald L'cream,25% flyash	f	\N	\N	2008-08-06 16:16:53.444434
67	mpa	3	mpa	\N	int	PK	\N	25,32,40,50,60,65,70,80	f	\N	\N	2008-08-06 16:16:53.450927
68	slump	3	slump	\N	int	PK	\N	100,180,60,90,80,190,70,140,150,130	f	\N	\N	2008-08-06 16:16:53.456939
69	sn	3	sn	\N	varchar	PK	\N	1,10,11,12,2,2001-1,2001-10,2001-11,2001-12,2001-13	f	\N	\N	2008-08-06 16:16:53.463072
70	sulphate	3	sulphate	\N	double	PK	\N	21.30000,,0.27700,19.30000,13.20000,0.00000	f	\N	\N	2008-08-06 16:16:53.475149
71	tdate	3	tdate	\N	datetime	PK	\N	2001-01-01 00:00:00,2003-06-20 00:00:00,2004-06-15 00:00:00,2004-08-30 00:00:00,2004-09-06 00:00:00,2004-09-07 00:00:00,2004-09-08 00:00:00,2004-09-13 00:00:00,2004-09-14 00:00:00,2004-09-15 00:00:00	f	\N	\N	2008-08-06 16:16:53.482589
72	testDev	3	testDev	\N	double	PK	\N	3.30000,-5.10000,-2.00000,-0.20000,0.00000,-1.00000,0.70000,1.70000,-0.70000,-1.30000	f	\N	\N	2008-08-06 16:16:53.488955
73	unitn	3	unitn	\N	varchar	PK	\N	panel number,,,608-0876,608-0880	f	\N	\N	2008-08-06 16:16:53.503089
115	emp_date	5	emp_date	\N	date	PK	\N	2008-04-01,2008-04-02,2008-04-03,2008-04-04,2008-04-05,2008-04-06,2008-04-07,2008-04-08,2008-04-09,2008-04-10	f	\N	\N	2008-08-06 16:16:55.852844
116	emp_name	5	emp_name	\N	varchar	PK	\N	Allen - Jesse,Allen - Kelley,Anderson - Brett,Anderson-Gibbs - Glenn-Rohan,Andrews - Russell,Appleton - Chris,Arthy - Justin,Atfield - Peter,Aylett - Daryl,Bailey - Chris	f	\N	\N	2008-08-06 16:16:55.861619
117	emp_reason	5	emp_reason	\N	varchar	PK	\N	,annual leave,court matters,injury miscellaneous,sick family member,sick with doctors cert,sick no doctors cert,paid bereavement,no reason,appointment	f	\N	\N	2008-08-06 16:16:55.870618
118	id	5	id	\N	int	PK	\N	4581,4582,4583,4584,4585,4586,4587,4588,4589,4590	f	\N	\N	2008-08-06 16:16:55.875568
119	id	6	id	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:16:55.881968
120	ir_actioned_by	6	ir_actioned_by	\N	varchar	PK	\N	K. Swain / J. Lynch,K. Swain,A. Friend,,Brad Greenhalgh,B. Sharpley,S. Kuhn,H. Vannek	f	\N	\N	2008-08-06 16:16:55.884437
121	ir_comment	6	ir_comment	\N	varchar	PK	\N	Training on reading of drawings.,Ensure ferrules secure prior to pour.,Ensure lifters have max cover to bed and are installed vertical.,Rework the welding jig to ensure that bars are placed perfectly to prevent the slotted shutter fowling when removed.,N/A Client issue.,Training - mould preparation & concrete acceptance,,Training - draftsman trained on drawing reissue procedures.,Training - ensure packing is sufficient to prevent point loading when units moved & stored.,Training - post pour acceptance must ensure class 2 finish.	f	\N	\N	2008-08-06 16:16:55.888042
122	ir_cost_estimate	6	ir_cost_estimate	\N	double	PK	\N	0	f	\N	\N	2008-08-06 16:16:55.89049
74	emp_allocation	4	emp_allocation	\N	varchar	PK	\N	Unallocated,PA582,PA614,PA618,PA588,PA608,PA625,PA603,PA564,PA626	f	\N	\N	2008-08-06 16:16:53.593007
77	emp_al_meal	4	emp_al_meal	\N	double	PK	\N	22.6,,12.5,0,9.6,25,50	f	\N	\N	2008-08-06 16:16:53.810076
78	emp_al_other	4	emp_al_other	\N	double	PK	\N	,52.5,192,6.13,617.6,169.62,346.32,36.75,814.5,865.8	f	\N	\N	2008-08-06 16:16:53.819749
79	emp_al_site	4	emp_al_site	\N	double	PK	\N	0,,9,7.125,11.5,10.25,43.65,6.5,14,10.875	f	\N	\N	2008-08-06 16:16:53.827853
80	emp_al_siterate	4	emp_al_siterate	\N	double	PK	\N	,0,1.25,4.5,0.25	f	\N	\N	2008-08-06 16:16:53.943419
81	emp_al_travel	4	emp_al_travel	\N	double	PK	\N	,27.5,17.85,0	f	\N	\N	2008-08-06 16:16:54.038007
82	emp_area	4	emp_area	\N	int	PK	\N	11,1,13,12,15,14,4,3,2,21	f	\N	\N	2008-08-06 16:16:54.045853
83	emp_baserate	4	emp_baserate	\N	decimal	PK	\N	1.50,	f	\N	\N	2008-08-06 16:16:54.151173
84	emp_benefit_bert	4	emp_benefit_bert	\N	double	PK	\N	0,12.87,,11.99	f	\N	\N	2008-08-06 16:16:54.248943
85	emp_benefit_cips	4	emp_benefit_cips	\N	double	PK	\N	0,2.6,	f	\N	\N	2008-08-06 16:16:54.356758
87	emp_clocknum	4	emp_clocknum	\N	int	PK	\N	\N	f	\N	\N	2008-08-06 16:16:54.446365
88	emp_date	4	emp_date	\N	date	PK	\N	0208-02-21,2007-12-31,2008-01-01,2008-01-02,2008-01-03,2008-01-04,2008-01-05,2008-01-06,2008-01-07,2008-01-08	f	\N	\N	2008-08-06 16:16:54.453067
89	emp_dept	4	emp_dept	\N	varchar	PK	\N	Administration,Precast,Prestress,Construction,Drafting,Concrete,Transport,Rigging,Logistics,Heathwood	f	\N	\N	2008-08-06 16:16:54.612309
90	emp_employer	4	emp_employer	\N	varchar	PK	\N	Resource,Lofa,Qld Procast,Procast,WorkPac	f	\N	\N	2008-08-06 16:16:54.738133
91	emp_holiday	4	emp_holiday	\N	varchar	PK	\N	,h,g	f	\N	\N	2008-08-06 16:16:54.846048
92	emp_holiday_amount	4	emp_holiday_amount	\N	double	PK	\N	0,230.57,150.92,181.49,306.94,188.74,177.56,209.64,188.65,257.9	f	\N	\N	2008-08-06 16:16:54.857526
93	emp_hr_finish	4	emp_hr_finish	\N	double	PK	\N	0.708333333333333,0.625,,0.395833333333333,0.333333333333333,0.6875,0.604166666666667,0.645833333333333,0.614583333333333,0.791666666666667	f	\N	\N	2008-08-06 16:16:54.864459
94	emp_hr_lunch	4	emp_hr_lunch	\N	double	PK	\N	1,0.4,,0.1,0.5,3.5,1.5,2.25,2,2.5	f	\N	\N	2008-08-06 16:16:54.885763
95	emp_hr_start	4	emp_hr_start	\N	double	PK	\N	0.291666666666667,,0.260416666666667,0.239583333333333,0.25,0.1875,0.197916666666667,0.208333333333333,0.427083333333333,0.21875	f	\N	\N	2008-08-06 16:16:54.890405
96	emp_hr_t	4	emp_hr_t	\N	double	PK	\N	9,7.6,0,2.4,0.999999999999999,8.25,10.25,8.75,8.5,10	f	\N	\N	2008-08-06 16:16:54.89529
97	emp_hr_th	4	emp_hr_th	\N	double	PK	\N	0,1.15,3,1.4,-8.88178419700125e-16,2,0.999999999999999,2.4,1.65,1.9	f	\N	\N	2008-08-06 16:16:54.90308
98	emp_hr_total	4	emp_hr_total	\N	double	PK	\N	9,7.6,0,2.4,0.999999999999999,8.25,10.25,8.75,8.5,10	t	\N	\N	2008-08-06 16:16:54.908031
99	emp_hr_travel	4	emp_hr_travel	\N	double	PK	\N	,3,2,1,5,1.5,2.5,4.2,5.5,4.5	f	\N	\N	2008-08-06 16:16:54.943202
100	emp_hr_tt	4	emp_hr_tt	\N	double	PK	\N	0,0.15,0.499999999999999,2,4,4.5,0.9,2.5,3,0.999999999999999	f	\N	\N	2008-08-06 16:16:54.953265
101	emp_id	4	emp_id	\N	int	PK	\N	\N	f	\N	\N	2008-08-06 16:16:55.06739
102	emp_injured	4	emp_injured	\N	varchar	PK	\N	,i	f	\N	\N	2008-08-06 16:16:55.20487
103	emp_injured_amount	4	emp_injured_amount	\N	double	PK	\N	0,177.56,188.74,,634.59,210.64,181.49,213.66,209.64,192.33	f	\N	\N	2008-08-06 16:16:55.295996
105	emp_loc	4	emp_loc	\N	varchar	PK	\N	HQ,Toowoomba,Heathwood,Brisbane,North Coast,	f	\N	\N	2008-08-06 16:16:55.410198
106	emp_name	4	emp_name	\N	varchar	PK	\N	Adam - Peter,Allen - Jesse,Allen - Kelley,Alvarez - Steven,Anderson - Brett,Anderson-Gibbs - Glenn-Rohan,Andrews - Russell,Appleton - Chris,Arthy - Justin,Atfield - Peter	t	\N	\N	2008-08-06 16:16:55.417769
107	emp_num	4	emp_num	\N	int	PK	\N	\N	f	\N	\N	2008-08-06 16:16:55.497662
108	emp_paytype	4	emp_paytype	\N	varchar	PK	\N	Salary,Wages 2,Lofa,Wages 1,Wages,Wages Rig,Wages Rig - Cas,WorkPac	f	\N	\N	2008-08-06 16:16:55.598819
109	emp_section	4	emp_section	\N	varchar	PK	\N	Admin,BMS,IT,Drafting,Estimating,Batching,Agitators,Driver,Maintenance,Casting	t	\N	\N	2008-08-06 16:16:55.603888
110	emp_sick	4	emp_sick	\N	varchar	PK	\N	,s,1	f	\N	\N	2008-08-06 16:16:55.705671
111	emp_sick_amount	4	emp_sick_amount	\N	double	PK	\N	0,188.74,177.56,181.49,230.57,204.95,275.89,201.24,159.12,196.1	f	\N	\N	2008-08-06 16:16:55.713752
112	emp_total_amount	4	emp_total_amount	\N	double	PK	\N	168.4965,121.5786,252.6336,298.4899,284.2109,189.4681,209.6179,0,193.6537,69.5856	f	\N	\N	2008-08-06 16:16:55.718259
113	emp_workdeal	4	emp_workdeal	\N	decimal	PK	\N	9.0,2.4,8.0,7.2,7.6	f	\N	\N	2008-08-06 16:16:55.81915
114	id	4	id	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:16:55.824042
75	emp_al_height	4	emp_al_height	\N	double	PK	\N	0,,3.096,2.451,3.956,3.526,4.171,2.236,4.816,3.741	f	\N	\N	2008-08-06 16:16:53.602195
76	emp_al_heightrate	4	emp_al_heightrate	\N	double	PK	\N	,0.43,0	f	\N	\N	2008-08-06 16:16:53.707898
123	ir_date_closed	6	ir_date_closed	\N	date	PK	\N	2008-05-15,2008-05-12,2008-05-07,1900-01-00,2008-05-06,2008-04-21,2008-04-22,2008-04-18,2008-04-16,2008-04-15	f	\N	\N	2008-08-06 16:16:55.892772
124	ir_date_raised	6	ir_date_raised	\N	date	PK	\N	2008-05-13,2008-05-12,2008-05-07,2008-05-06,2008-04-28,1900-01-00,2008-04-18,2008-04-17,2008-04-16,2008-04-15	f	\N	\N	2008-08-06 16:16:55.895156
125	ir_date_review	6	ir_date_review	\N	date	PK	\N	1900-01-00	f	\N	\N	2008-08-06 16:16:55.897581
126	ir_date_toolbox	6	ir_date_toolbox	\N	date	PK	\N	1900-01-00	f	\N	\N	2008-08-06 16:16:55.899894
127	ir_desc	6	ir_desc	\N	varchar	PK	\N	582-05-ZU-001 cast mirror reverse. Drawing read incorrectly. Human error.,Units 614-A1-LG-117 & 125 have ferrules missing.,Bed face popped out below lifters. Making the lifters unsafe for use. Panels 626-P-123 & 125.,582-MCK1-M2-006 has stress cracks.,623-03-009 poured correctly and now revised by client due to site information.,624-T2-136 & 137 have bad discolouration & unsound concrete.,Job 614: Heathwood columns have HD bolts set at 120 centres and should be 130,603-08110 poured to rev A, rev B not issued to production as per standard document control procedure.,Bridge barriers 582-6D-G4-001, G12-001, G16-001 have cracks around hold down plates. This is due to handling damage.,582-MCK1-M2-004 & 008 returned from site with bugholes & cracks.	f	\N	\N	2008-08-06 16:16:55.902301
128	ir_desc_suggested	6	ir_desc_suggested	\N	varchar	PK	\N	This unit conforms to 582-05-ZW-001 that has not been cast. Renumber the cast unit & reschedule ZU-001 where ZW-001 was to be cast.,Chemset bars to replace ferrules.,Reject panels & repour.,Reject unit & repour.,Reject units & repour.,Rework brackets on site to suit,Reject unit / cast insitu on site.,Cut openings to the conforming size.,Unit accepted,Reject units, amend drawings & repour.	f	\N	\N	2008-08-06 16:16:55.904721
129	ir_initiator	6	ir_initiator	\N	varchar	PK	\N	Kris Swain,,Brad Greenhalgh,Max,Hans Vannek,Darren Langham,Aaron Leanneret from Matthews	f	\N	\N	2008-08-06 16:16:55.907213
130	ir_location	6	ir_location	\N	varchar	PK	\N	Toowoomba Moulds  ,Toowoomba Main Hall  ,Drafting,Toowoomba Backyard  ,Toowoomba Logistics,,Toowoomba Finishing,Rigging,Toowoomba Shed 35  ,Toowoomba Scheduling	f	\N	\N	2008-08-06 16:16:55.909521
131	ir_number	6	ir_number	\N	varchar	PK	\N	T213,T214,T215,T216,T217,T218,T219,T220,T221,T222	f	\N	\N	2008-08-06 16:16:55.911901
132	ir_rejected	6	ir_rejected	\N	varchar	PK	\N	no,yes	f	\N	\N	2008-08-06 16:16:55.914242
133	ir_reject_point	6	ir_reject_point	\N	varchar	PK	\N	Fabrication,Manufacture,Rigging,,Transport,Finishing,Drafting,Storage	f	\N	\N	2008-08-06 16:16:55.916999
134	ir_reviewed_by	6	ir_reviewed_by	\N	varchar	PK	\N	,	f	\N	\N	2008-08-06 16:16:55.919325
135	ir_scope	6	ir_scope	\N	varchar	PK	\N	Quality,	f	\N	\N	2008-08-06 16:16:55.921591
136	ir_source	6	ir_source	\N	varchar	PK	\N	Employee,Customer,,Internal Audit	f	\N	\N	2008-08-06 16:16:55.924326
137	ir_status	6	ir_status	\N	varchar	PK	\N	Complete,Approved For Action,	f	\N	\N	2008-08-06 16:16:55.926711
138	ir_type	6	ir_type	\N	varchar	PK	\N	Non Conformance,Defective Product,,Concern,Improvement Recommendation	f	\N	\N	2008-08-06 16:16:55.929469
139	ir_unitn	6	ir_unitn	\N	varchar	PK	\N	582-05-ZU-001,Units 614-A1-LG-117,Units 614-A1-LG-125,626-P-123,626-P-125,582-MCK1-M2-006,623-03-009,624-T2-136,624-T2-137,	f	\N	\N	2008-08-06 16:16:55.931783
146	client	7	client	\N	varchar	PK	\N	ProcastAust,PROCAST,Test Panels,Canning,Prowse,Baulderstone Hornibrook,Sunland,Adco,North & Mort Pty Ltd,FKG	f	\N	\N	2008-08-06 16:16:56.373331
147	conC	7	conC	\N	varchar	PK	\N	PRO-1,,PRO-71,PRO-76,PRO-65,PRO-35,PRO-20,0,PRO-66,PRO-70	f	\N	\N	2008-08-06 16:16:56.395681
148	delD	7	delD	\N	datetime	PK	\N	1999-01-01 00:00:00,2007-01-07 00:00:00,2007-01-20 00:00:00,,2006-11-02 00:00:00,2006-09-07 00:00:00,2005-09-15 00:00:00,2005-09-12 00:00:00,2005-09-03 00:00:00,2005-09-02 00:00:00	f	\N	\N	2008-08-06 16:16:56.403743
149	draftD	7	draftD	\N	datetime	PK	\N	1999-01-01 00:00:00,2007-01-05 00:00:00,2007-01-18 00:00:00,2006-11-24 00:00:00,2007-06-15 00:00:00,2007-05-10 00:00:00,,2007-05-15 00:00:00,2006-11-03 00:00:00,2006-11-17 00:00:00	t	\N	\N	2008-08-06 16:16:56.410467
150	drafter	7	drafter	\N	varchar	PK	\N	Bernie G,Ben P,Shane M,Nick O,,Kieth C,Peter R,Tristan N,Steve M,Nicholas H	t	\N	\N	2008-08-06 16:16:56.416841
151	draR	7	draR	\N	int	PK	\N	5,2,1,3,	f	\N	\N	2008-08-06 16:16:56.594034
152	draV	7	draV	\N	int	PK	\N	1,20,,119,0,80,181,52,46,162	t	\N	\N	2008-08-06 16:16:56.60417
153	erectD	7	erectD	\N	datetime	PK	\N	1999-01-01 00:00:00,2007-01-07 00:00:00,2007-01-20 00:00:00,,2006-09-07 00:00:00,2005-09-15 00:00:00,2005-09-12 00:00:00,2005-09-03 00:00:00,2005-09-02 00:00:00,2005-09-13 00:00:00	f	\N	\N	2008-08-06 16:16:56.610736
154	ID	7	ID	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:16:56.617543
155	jobD	7	jobD	\N	varchar	PK	\N	Data,Patching Pit,Waterbird Habitat,Panel Rack,MKY-01001,MKY-01002,MKY-01003,MKY-01004,MKY-01005,MKY-01006	f	\N	\N	2008-08-06 16:16:56.624539
156	jobN	7	jobN	\N	int	PK	\N	987,999,100,296,505,513,526,528,529,530	f	\N	\N	2008-08-06 16:16:56.636058
157	len	7	len	\N	double	PK	\N	4.32100,4.47000,5.15000,5.60000,5.00000,6.40300,6.00000,3.50000,2.75500,7.58600	f	\N	\N	2008-08-06 16:16:56.647756
158	lenC	7	lenC	\N	double	PK	\N	4.32100,4.47000,5.15000,5.60000,5.00000,6.40300,,2.38000,2.19000,3.75000	f	\N	\N	2008-08-06 16:16:56.654863
159	loadN	7	loadN	\N	varchar	PK	\N	L9-001,L999-002,,505-001,3,3/01/1900,2/01/1900,1/01/1900,529-005,529-001	f	\N	\N	2008-08-06 16:16:56.663424
160	manD	7	manD	\N	datetime	PK	\N	1999-01-01 00:00:00,2007-01-05 00:00:00,2007-01-18 00:00:00,2006-12-18 00:00:00,2007-06-21 00:00:00,2007-05-17 00:00:00,,2006-10-19 00:00:00,2006-10-31 00:00:00,2006-11-03 00:00:00	f	\N	\N	2008-08-06 16:16:56.669414
161	mpa1	7	mpa1	\N	varchar	PK	\N	mpa1,,553-05024,553-05050,553-05060,17/03/2007,553-07023,not on quote,combined with 08023,09/09/1999	f	\N	\N	2008-08-06 16:16:56.697559
162	mpa28	7	mpa28	\N	double	PK	\N	32.10000,,36412.00000	f	\N	\N	2008-08-06 16:16:56.865449
163	mpa7	7	mpa7	\N	double	PK	\N	32.10000,,36412.00000	f	\N	\N	2008-08-06 16:16:57.032554
164	ncr1	7	ncr1	\N	int	PK	\N	1,,36412	f	\N	\N	2008-08-06 16:16:57.212353
165	ncr2	7	ncr2	\N	int	PK	\N	1,,36412	f	\N	\N	2008-08-06 16:16:57.375938
166	ncr3	7	ncr3	\N	int	PK	\N	1,,36412	f	\N	\N	2008-08-06 16:16:57.539128
167	ncr4	7	ncr4	\N	varchar	PK	\N	1,,,Coopers Plains,Runcorn,Fruitgrove,Altandi,Banoon,Sunnybank,Kuraby	f	\N	\N	2008-08-06 16:16:57.585237
168	ncr5	7	ncr5	\N	int	PK	\N	1,	f	\N	\N	2008-08-06 16:16:57.771641
169	ncr6	7	ncr6	\N	int	PK	\N	1,	f	\N	\N	2008-08-06 16:16:58.08897
170	polV	7	polV	\N	int	PK	\N	1,10,,0	f	\N	\N	2008-08-06 16:16:58.371634
171	pourR	7	pourR	\N	int	PK	\N	5,,2	f	\N	\N	2008-08-06 16:16:58.615147
172	preR	7	preR	\N	int	PK	\N	5,,1,3,2,4,6	f	\N	\N	2008-08-06 16:16:58.866941
142	appDqa2	7	appDqa2	\N	datetime	PK	\N	1999-01-01 00:00:00,2007-01-05 00:00:00,2007-01-18 00:00:00,2006-12-18 00:00:00,	f	\N	\N	2008-08-06 16:16:56.341575
143	area	7	area	\N	double	PK	\N	5.33200,3.12900,3.60500,3.92000,2.35000,14.09000,6.60000,1.12000,8.42000,7.81000	f	\N	\N	2008-08-06 16:16:56.347762
144	areaC	7	areaC	\N	double	PK	\N	5.33200,3.12900,3.60500,3.92000,2.35000,14.09000,,1.12000,12.92300,7.14000	f	\N	\N	2008-08-06 16:16:56.355118
145	blaV	7	blaV	\N	int	PK	\N	1,10,,0,86,116,146,87,78,112	f	\N	\N	2008-08-06 16:16:56.365934
196	delD	8	delD	\N	datetime	PK	\N	2006-09-18 00:00:00,2006-09-08 00:00:00,,2006-10-23 00:00:00,2006-10-24 00:00:00,2006-10-25 00:00:00,2007-04-24 00:00:00,2006-08-23 00:00:00,2006-08-25 00:00:00,2006-08-30 00:00:00	f	\N	\N	2008-08-06 16:16:59.945275
197	ID	8	ID	\N	int	PK	\N	1,2,3,4,5,6,19,37,65,66	f	\N	\N	2008-08-06 16:16:59.948884
198	loadN	8	loadN	\N	varchar	PK	\N	L531-001,531-002,531-003,547-001,547-002,547-003,547-004,547-005,547-006,547-007	f	\N	\N	2008-08-06 16:16:59.952788
199	loc	8	loc	\N	varchar	PK	\N	store,,COOPERS PLAINS,BANOON,FRUITGROVE,SUNNYBANK,RUNCORN,ALTANDI,ALL ON SAME LOAD AS  LOAD 188,Kuraby	f	\N	\N	2008-08-06 16:16:59.968108
200	reqD	8	reqD	\N	datetime	PK	\N	2006-09-18 00:00:00,2006-09-08 00:00:00,2006-10-23 00:00:00,2006-10-24 00:00:00,2006-10-25 00:00:00,2007-04-24 00:00:00,2006-08-23 00:00:00,2006-08-25 00:00:00,2006-08-30 00:00:00,2006-09-05 00:00:00	f	\N	\N	2008-08-06 16:16:59.972125
201	trailN	8	trailN	\N	varchar	PK	\N	A4,14,A5,15,A3,13,A1,11,12,A2	f	\N	\N	2008-08-06 16:16:59.977254
202	truckD	8	truckD	\N	varchar	PK	\N	Mick O'Shea,Dave Ryan,,Bruce Swan,Kris Lynstrad,Col Doyle,Danny Hennessy,KEEGAN,DAN LYNCH,D&K WEISS	f	\N	\N	2008-08-06 16:16:59.985034
203	truckN	8	truckN	\N	varchar	PK	\N	Mitsubishi,Kenworth,,White Trident,Superliner,Blue Trident,Western Star,F12,VOLVO,C.O. W STAR	f	\N	\N	2008-08-06 16:16:59.99139
204	unitN	8	unitN	\N	varchar	PK	\N	e20,e21,e22,e23,e24,e25,531-00013,531-00012,531-00011,531-00008	f	\N	\N	2008-08-06 16:16:59.994716
205	ID	9	ID	\N	int	PK	\N	1,2,3,4,5,6	f	\N	\N	2008-08-06 16:16:59.999283
206	numbers	9	numbers	\N	int	PK	\N	46464646,409872058,417198718,417751828,431533588,448955040	f	\N	\N	2008-08-06 16:17:00.002765
207	id	10	id	\N	int	PK	\N	1434,1435,1436,1437,1438,1439,1440,1441,1442,1443	f	\N	\N	2008-08-06 16:17:00.028523
208	lenc	10	lenc	\N	double	PK	\N	13.5,20,21.5,21.8,22,24,25.8,8,9,10	f	\N	\N	2008-08-06 16:17:00.036059
209	mand	10	mand	\N	date	PK	\N	2009-08-05,2009-08-06,2009-08-07,2009-08-10,2009-08-11,2009-08-12,2009-08-13,2009-08-14,2009-04-27,2009-04-28	f	\N	\N	2008-08-06 16:17:00.039112
210	stress_bed	10	stress_bed	\N	varchar	PK	\N	DUSB1,DUSB2,PSB1,PSB2,TRSB1,TRSB2	f	\N	\N	2008-08-06 16:17:00.043688
211	unitn	10	unitn	\N	varchar	PK	\N	642-C301-NB-1/01,642-C301-NB-1/02,642-C301-NB-1/03,642-C301-NB-1/04,642-C301-NB-1/05,642-C301-NB-1/06,642-C301-NB-1/07,642-C301-NB-1/08,642-C301-NB-1/09,642-C301-NB-1/10	f	\N	\N	2008-08-06 16:17:00.046592
212	id	11	id	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:17:00.076764
213	rec_batch	11	rec_batch	\N	int	PK	\N	12,14	f	\N	\N	2008-08-06 16:17:00.126793
214	rec_datetime	11	rec_datetime	\N	datetime	PK	\N	2008-07-16 17:29:55,2008-07-16 17:30:05,2008-07-16 17:30:15,2008-07-16 17:30:25,2008-07-16 17:30:35,2008-07-16 17:30:45,2008-07-16 17:30:55,2008-07-16 17:31:05,2008-07-16 17:31:15,2008-07-16 17:31:25	f	\N	\N	2008-08-06 16:17:00.129169
215	rec_inspec	11	rec_inspec	\N	int	PK	\N	1	f	\N	\N	2008-08-06 16:17:00.170333
216	rec_loc	11	rec_loc	\N	varchar	PK	\N	Bed 2 Zone 1 A,Bed 2 Zone 1 B,Bed 2 Zone 2 A,Bed 2 Zone 2 B,Bed 2 Zone 3 A,Bed 2 Zone 3 B,Bed 2 Zone 4 A,Bed 2 Zone 4 B	f	\N	\N	2008-08-06 16:17:00.255317
217	rec_max	11	rec_max	\N	double	PK	\N	20.1000003814697,20.6000003814697,21.1000003814697,21.6000003814697,21.8000011444092,21.5,21.2000007629395,20.8000011444092,59.7000007629395,59.5	f	\N	\N	2008-08-06 16:17:00.257656
218	rec_mean	11	rec_mean	\N	double	PK	\N	19.2400054931641,19.5436916351318,20.0311012268066,20.5779571533203,20.8806781768799,20.8848781585693,20.6374835968018,19.7848834991455,20.1000003814697,20.6000003814697	f	\N	\N	2008-08-06 16:17:00.259998
219	rec_min	11	rec_min	\N	double	PK	\N	0,20.1000003814697,20.6000003814697,21.1000003814697,21.6000003814697,21.5,20.8999996185303,20.2000007629395,21.7000007629395,21.3999996185303	f	\N	\N	2008-08-06 16:17:00.26226
220	rec_nerror	11	rec_nerror	\N	int	PK	\N	1	f	\N	\N	2008-08-06 16:17:00.30416
221	rec_tagno	11	rec_tagno	\N	int	PK	\N	9,10,11,12,13,14,15,16	f	\N	\N	2008-08-06 16:17:00.34347
222	rec_value	11	rec_value	\N	double	PK	\N	20.1000003814697,20.6000003814697,21.1000003814697,21.6000003814697,21.7000007629395,21.3999996185303,20.8999996185303,21.5,20.2000007629395,21.8999996185303	f	\N	\N	2008-08-06 16:17:00.345805
223	client	12	client	\N	int	PK	\N	\N	f	\N	\N	2008-08-06 16:17:00.361531
224	ID	12	ID	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:17:00.365474
225	jobDivision	12	jobDivision	\N	varchar	PK	\N	Tower Panels,Lift & Stair,Noise Barriers,Beams,Columns,Floors,Walls,Basement,Lindfield Units,Balustrades	f	\N	\N	2008-08-06 16:17:00.377271
178	specS	7	specS	\N	varchar	PK	\N	40 no color,40,,40 Peach,40 Caramel,40 Bluestone,40 Charcoal,40 Light Peach,40 MRD,50	f	\N	\N	2008-08-06 16:16:59.408124
179	status	7	status	\N	varchar	PK	\N	S,,deleted,not used,Combined,Addition,Split,09/09/1999,9/09/1999,A	f	\N	\N	2008-08-06 16:16:59.449934
180	thi	7	thi	\N	double	PK	\N	0.12500,0.15000,0.20000,0.25000,,0.09000,0.17500,0.30000,0.19000,0.22500	f	\N	\N	2008-08-06 16:16:59.457599
181	thiC	7	thiC	\N	double	PK	\N	0.12500,0.15000,0.20000,,0.00000,0.30000,0.16500,0.17500,0.26000,0.27000	f	\N	\N	2008-08-06 16:16:59.486445
182	totalV	7	totalV	\N	int	PK	\N	7,340,,5217,3225,4872,4688,4512,4332,4153	f	\N	\N	2008-08-06 16:16:59.493539
183	trailN	7	trailN	\N	varchar	PK	\N	T-999,,C/T,A2,DD3,DROP,A1,MAZDA,11,13	f	\N	\N	2008-08-06 16:16:59.50762
184	traV	7	traV	\N	int	PK	\N	1,100,,328,181,302,289,275,262,249	f	\N	\N	2008-08-06 16:16:59.514229
186	truckN	7	truckN	\N	varchar	PK	\N	T-999,,Mitsubishi,Kenworth,Superliner,Blue Trident,White Trident,868-FNW,Western Star,White mack	f	\N	\N	2008-08-06 16:16:59.55642
187	unitD	7	unitD	\N	varchar	PK	\N	Panel No. 00017,Panel No. 00018,Panel No. 00019,Panel No. 00020,Panel No. 00021,Panel No. 00022,Panel No. 00023,Panel No. 00024,Panel No. 00001,Panel No. 00002	f	\N	\N	2008-08-06 16:16:59.563151
188	unitN	7	unitN	\N	varchar	PK	\N	100-08001,100-08002,100-08003,100-08004,100-08005,1002-RL-001,1002-RL-002,1002-RL-003,1002-RL-004,1002-RL-005	t	\N	\N	2008-08-06 16:16:59.569303
190	var	7	var	\N	varchar	PK	\N	added,,Deleted,stock,combined,repour,insitu,renumbered,split,	f	\N	\N	2008-08-06 16:16:59.644862
191	vol	7	vol	\N	double	PK	\N	0.66700,0.47000,0.55000,0.59000,0.35300,2.82000,0.99000,0.28000,1.21000,0.90000	t	\N	\N	2008-08-06 16:16:59.650963
192	volC	7	volC	\N	double	PK	\N	0.66700,0.47000,0.55000,0.59000,0.35300,2.82000,,0.28000,0.00000,0.94100	f	\N	\N	2008-08-06 16:16:59.660119
193	welV	7	welV	\N	int	PK	\N	1,10,,0	f	\N	\N	2008-08-06 16:16:59.872961
194	wid	7	wid	\N	double	PK	\N	,0.00000,0.00900,0.03100,0.08000,0.14800,0.15000,0.19800,0.20000,0.20100	f	\N	\N	2008-08-06 16:16:59.913032
195	widC	7	widC	\N	double	PK	\N	1.23400,0.70000,0.47000,2.40000,,5.43000,3.00000,5.00500,4.78600,4.56600	f	\N	\N	2008-08-06 16:16:59.920055
174	qty	7	qty	\N	int	PK	\N	1,,0	f	\N	\N	2008-08-06 16:16:59.043281
175	qtyC	7	qtyC	\N	int	PK	\N	1,,0	f	\N	\N	2008-08-06 16:16:59.280517
176	rev	7	rev	\N	varchar	PK	\N	A,,B,D,C,F,E,L,H,G	f	\N	\N	2008-08-06 16:16:59.377959
177	rigV	7	rigV	\N	int	PK	\N	1,100,,513,0,290,387,397,587,267	f	\N	\N	2008-08-06 16:16:59.3882
226	jobName	12	jobName	\N	varchar	PK	\N	South Central,Mount Lindsay Highway,MIM Zinc Lead Concentrator,Helensvale Mixed Development,Brisbane Central,Aldi Warwick,Glen Innes,Wesley House,Southbank Student Village,110 Mary Street	f	\N	\N	2008-08-06 16:17:00.399157
227	jobStatus	12	jobStatus	\N	varchar	PK	\N	pending	f	\N	\N	2008-08-06 16:17:00.423552
228	jobType	12	jobType	\N	int	PK	\N	1,2,3	f	\N	\N	2008-08-06 16:17:00.437537
229	len	12	len	\N	double	PK	\N	6.15000,3.86700,6.21000,6.12600,6.08200,6.03700,5.99300,5.94900,5.90400,5.85600	f	\N	\N	2008-08-06 16:17:00.443462
230	m2	12	m2	\N	double	PK	\N	10.51000,10.60900,10.60300,9.80900,9.80300,10.55300,10.45600,10.55700,10.65000,10.46000	f	\N	\N	2008-08-06 16:17:00.447236
231	m3	12	m3	\N	double	PK	\N	1.83900,1.85700,1.85500,1.71700,1.71600,1.84700,1.83000,1.86400,1.83100,1.74000	f	\N	\N	2008-08-06 16:17:00.451602
232	qty	12	qty	\N	int	PK	\N	1,	f	\N	\N	2008-08-06 16:17:00.468093
233	thi	12	thi	\N	double	PK	\N	0.17500,0.20000,0.15000,,0.10000,1.30000,0.65000,0.60000,0.18000,0.12000	f	\N	\N	2008-08-06 16:17:00.478703
234	unitN	12	unitN	\N	varchar	PK	\N	P1001-01101,P1001-01101a,P1001-01102,P1001-01102a,P1001-01103,P1001-01103a,P1001-01104,P1001-01104a,P1001-01105,P1001-01106	f	\N	\N	2008-08-06 16:17:00.48434
235	wid	12	wid	\N	double	PK	\N	,0.35000,0.35700,0.36300,0.38000,0.39000,0.40000,0.41000,0.41900,0.42000	f	\N	\N	2008-08-06 16:17:00.490437
236	area	13	area	\N	double	PK	\N	5.1,11.4,24.22,26.46,24.67,14.11,8.72,8.56,22.94,15.25	f	\N	\N	2008-08-06 16:17:00.498214
237	bla_labor	13	bla_labor	\N	double	PK	\N	0	f	\N	\N	2008-08-06 16:17:00.501104
238	bla_val	13	bla_val	\N	double	PK	\N	0	f	\N	\N	2008-08-06 16:17:00.503952
239	dra_val	13	dra_val	\N	double	PK	\N	269.73	f	\N	\N	2008-08-06 16:17:00.506753
240	id	13	id	\N	int	PK	\N	1,2,3,4,5,6,7,8,9,10	f	\N	\N	2008-08-06 16:17:00.508805
241	len	13	len	\N	double	PK	\N	6,5.7,8.97,3.17,15.29,11.9,11.8,11.7,8,9.27	f	\N	\N	2008-08-06 16:17:00.511156
242	loads	13	loads	\N	double	PK	\N	0.1,0.2,0.5,0.6,0.3,0.4,0	f	\N	\N	2008-08-06 16:17:00.51415
243	mass	13	mass	\N	double	PK	\N	1.913,4.275,9.082,9.923,9.25,9.214,5.29,3.269,3.21,11.468	f	\N	\N	2008-08-06 16:17:00.516384
244	mesh	13	mesh	\N	int	PK	\N	1018	f	\N	\N	2008-08-06 16:17:00.519185
245	pour_date	13	pour_date	\N	date	PK	\N	2007-11-27,2007-11-28,2007-11-29,2007-11-30,2007-12-01,2007-12-02,2007-12-03,2007-12-04,2007-12-05,2007-12-06	f	\N	\N	2008-08-06 16:17:00.52241
246	pre_area	13	pre_area	\N	int	PK	\N	1	f	\N	\N	2008-08-06 16:17:00.525336
247	pre_labor	13	pre_labor	\N	double	PK	\N	114.49,255.92,543.7,594.04,553.77,316.68,195.7,192.14,686.5,342.33	f	\N	\N	2008-08-06 16:17:00.527763
248	pre_val	13	pre_val	\N	double	PK	\N	608.95,1258.62,2625.23,2856.87,2671.08,2877.49,1554.54,978.84,962.46,3313.16	f	\N	\N	2008-08-06 16:17:00.530048
249	qty	13	qty	\N	int	PK	\N	1	f	\N	\N	2008-08-06 16:17:00.53285
250	rig_labor	13	rig_labor	\N	double	PK	\N	176.06	f	\N	\N	2008-08-06 16:17:00.535738
251	rig_val	13	rig_val	\N	double	PK	\N	694.93	f	\N	\N	2008-08-06 16:17:00.538633
252	specs	13	specs	\N	varchar	PK	\N	40 mPa	f	\N	\N	2008-08-06 16:17:00.541602
253	steelrate_m3	13	steelrate_m3	\N	double	PK	\N	113,81,76,74,84,82,87,88,86,125	f	\N	\N	2008-08-06 16:17:00.543835
254	steelrate_unit	13	steelrate_unit	\N	double	PK	\N	86.5,138.8,276,294.9,279.5,309.1,172.9,114.4,113,394.7	f	\N	\N	2008-08-06 16:17:00.546056
255	thi	13	thi	\N	double	PK	\N	0.15,0.2	f	\N	\N	2008-08-06 16:17:00.548947
256	total_val	13	total_val	\N	double	PK	\N	1646.15,2385.44,3934.39,4197.92,3986.62,4218.54,2719.85,2067.5,2048.87,4712.8	f	\N	\N	2008-08-06 16:17:00.551192
257	tra_val	13	tra_val	\N	double	PK	\N	72.54,162.16,344.5,376.39,350.88,200.65,124,121.75,434.98,216.91	f	\N	\N	2008-08-06 16:17:00.55343
258	unitn	13	unitn	\N	varchar	PK	\N	614-W1-101,614-W1-102,614-W1-103,614-W1-104,614-W1-105,614-W1-106,614-W1-107,614-W1-108,614-W1-109,614-W1-110	f	\N	\N	2008-08-06 16:17:00.555501
259	unit_type	13	unit_type	\N	int	PK	\N	1	f	\N	\N	2008-08-06 16:17:00.559549
260	vol	13	vol	\N	double	PK	\N	0.8,1.7,3.6,4,3.7,2.1,1.3,4.6,2.3,1.6	f	\N	\N	2008-08-06 16:17:00.561836
261	wel_labor	13	wel_labor	\N	double	PK	\N	0	f	\N	\N	2008-08-06 16:17:00.564661
262	wel_val	13	wel_val	\N	double	PK	\N	0	f	\N	\N	2008-08-06 16:17:00.567549
263	wid	13	wid	\N	double	PK	\N	0.85,2,2.7,2.95,2.75,4.45,1.5,1.7,1.2,2.2	f	\N	\N	2008-08-06 16:17:00.569878
1	date	1	date	\N	date	PK	\N	2008-01-01,2008-01-02,2008-01-03,2008-01-04,2008-01-05,2008-01-06,2008-01-07,2008-01-08,2008-01-09,2008-01-10	t	\N	\N	2008-08-06 16:16:52.873014
140	appDd	7	appDd	\N	datetime	PK	\N	1999-01-01 00:00:00,	f	\N	\N	2008-08-06 16:16:56.136413
141	appDqa1	7	appDqa1	\N	datetime	PK	\N	1999-01-01 00:00:00,2007-01-05 00:00:00,2007-01-18 00:00:00,2006-12-18 00:00:00,,2006-08-22 00:00:00,2006-08-25 00:00:00,2006-08-14 00:00:00,1960-08-25 00:00:00,2006-06-30 00:00:00	f	\N	\N	2008-08-06 16:16:56.167636
173	preV	7	preV	\N	int	PK	\N	1,100,,4256,2411,3936,3766,3603,3437,3271	f	\N	\N	2008-08-06 16:16:58.882027
185	truckD	7	truckD	\N	varchar	PK	\N	Strop,,Mick O'Shea,Danny Hennessy,Dave Ryan,Kris Lyngstad,Bruce Swan,Col Doyle,Kris Lynstrad,Paul Byrne	f	\N	\N	2008-08-06 16:16:59.530008
189	unitT	7	unitT	\N	varchar	PK	\N	Panel,,Stair 15. EE1. Stair Detail Sheet5. LC,Big W. AE5. PS1. Ground Level,Big W. AE6. PS1. Ground Level,Big W. AE7. PS1. Ground Level,Big W. AE9. PS1. Ground Level,Big W. AE10. PS1. Ground Level,Big W. AE10 cont. PS1. Ground Level,Big W. AE4. PS1. Ground Level	f	\N	\N	2008-08-06 16:16:59.576416
86	emp_benefit_super	4	emp_benefit_super	\N	double	PK	\N	12.0465,10.0386,18.9936,22.7799,21.6009,13.7781,17.3079,0,19.0377,14.1237	f	\N	\N	2008-08-06 16:16:54.364467
104	emp_jobname	4	emp_jobname	\N	varchar	PK	\N	Payroll Officer,Admin Assistant,HR/WHSO,Contracts Administrator,BMS & Technical Manager,,IT Administrator,Administration Manager,Accounts,Admin	f	\N	\N	2008-08-06 16:16:55.300865
264	id	14	id	\N	int	PK	\N	110,111,112,113,114,115,116,117,118,119	f	\N	\N	2008-08-06 16:17:00.588118
265	job_status	14	job_status	\N	int	PK	\N	1,	t	\N	\N	2008-08-06 16:17:00.608666
266	mand	14	mand	\N	date	PK	\N	2008-05-09,2008-05-16,2008-05-15,2008-05-14,2008-05-13,2008-05-12,2008-05-01,2008-04-30,2008-04-29,2008-04-28	t	\N	\N	2008-08-06 16:17:00.612438
267	pro_area	14	pro_area	\N	int	PK	\N	1,4,2,3,5,6,9,10,7,8	t	\N	\N	2008-08-06 16:17:00.630304
268	unitn	14	unitn	\N	varchar	PK	\N	100-08001,100-08002,100-08003,100-08004,100-08005,1003-101,1003-102,1003-103,1003-104,1003-105	t	\N	\N	2008-08-06 16:17:00.633215
\.


--
-- Data for Name: databases; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY databases (database_id, name, object_id, host, username, "password", human_name, description, notes, records, modified_by, modified_time) FROM stdin;
1	db6	1	localhost	root	darkstar	Procast	\N	\N	225197	\N	2008-08-06 16:16:52.80011
\.


--
-- Data for Name: foo; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY foo  FROM stdin;
\.


--
-- Data for Name: list_constraints; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY list_constraints (list_constraints_id, template_id, column_id, "type", value, choose) FROM stdin;
69	1	266	gt	2008-06-30	f
\.


--
-- Data for Name: list_templates; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY list_templates (list_template_id, template_id, column_id, duplicates, subtotal, sort, "aggregate", label, optional, col_order) FROM stdin;
460	1	266	t	t	ASC	0	\N	t	1
461	1	191	t	t	ASC	sum	\N	t	2
408	2	1	t	t	ASC	0	\N	f	1
409	2	109	t	t	ASC	0	\N	f	2
410	2	98	t	t	ASC	sum	\N	f	3
\.


--
-- Data for Name: modules; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY modules (module_id, name, description, "type", subtype, status) FROM stdin;
admin	IronData Administration Functionality	This is the administration module. Do not remove this module.	Core	\N	active
catalogue	Data Source Catalogue	Store and manipulate the available data sources.	Core	\N	active
type_postgresql	PostgreSQL Data Source	Store and manipulate PostgreSQL data sources.	Data Source	Database	active
\.


--
-- Data for Name: objects; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY objects (object_id, name, "type", module_id, modified_by, modified_time) FROM stdin;
1	db6	mysql	\N	\N	2008-08-06 16:16:52.769296
\.


--
-- Data for Name: people; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY people (person_id, given_names, family_name, "password", "session", session_time, modified_by, modified_time) FROM stdin;
admin	Administration	User	21232f297a57a5a743894a0e4a801fc3	\N	\N	\N	2008-08-06 15:19:01.900181
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY permissions (permission_id, element, "function", permission, person_id, modified_by, modified_time) FROM stdin;
\.


--
-- Data for Name: simplegraph_constraints; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY simplegraph_constraints (autotable_constraints_id, template_id, column_id, "type", value, choose) FROM stdin;
\.


--
-- Data for Name: simplegraph_templates; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY simplegraph_templates (autotable_template_id, template_id, column_id, sort, "aggregate", axis) FROM stdin;
38	6	191	\N	sum	C
39	6	266	ASC	\N	X
\.


--
-- Data for Name: table_joins; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY table_joins (table_join_id, table1, table2, method) FROM stdin;
1	1	7	1,149
2	7	1	149,1
3	4	1	88,1
4	1	4	1,88
5	14	7	268,188
6	7	14	188,268
7	1	14	1,266
8	14	1	266,1
\.


--
-- Data for Name: tables; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY tables (table_id, name, database_id, human_name, description, notes, records, modified_by, modified_time) FROM stdin;
1	calendar	1	calendar	\N	\N	186	\N	2008-08-06 16:16:52.86825
2	calldetails	1	calldetails	\N	\N	518	\N	2008-08-06 16:16:52.875591
3	conc	1	conc	\N	\N	4724	\N	2008-08-06 16:16:53.236028
4	employees	1	employees	\N	\N	45156	\N	2008-08-06 16:16:53.578952
5	emp_sched2	1	emp_sched2	\N	\N	41407	\N	2008-08-06 16:16:55.84367
6	issues_register2	1	issues_register2	\N	\N	108	\N	2008-08-06 16:16:55.877169
7	jobs	1	jobs	\N	\N	45547	\N	2008-08-06 16:16:55.993049
8	loads	1	loads	\N	\N	19269	\N	2008-08-06 16:16:59.939354
9	numbersallowed	1	numbersallowed	\N	\N	6	\N	2008-08-06 16:16:59.995962
10	prestr_elements	1	prestr_elements	\N	\N	1433	\N	2008-08-06 16:17:00.006118
11	prestr_steamdata	1	prestr_steamdata	\N	\N	41376	\N	2008-08-06 16:17:00.071471
12	priced	1	priced	\N	\N	7334	\N	2008-08-06 16:17:00.35473
13	pricedwork	1	pricedwork	\N	\N	628	\N	2008-08-06 16:17:00.493304
14	pro_sched	1	pro_sched	\N	\N	17505	\N	2008-08-06 16:17:00.583252
\.


--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: evan
--

COPY templates (template_id, name, draft, module, object_id) FROM stdin;
1	Drafting Report	t	listing	1
2	Labour per m3	t	listing	1
3	Drafter Qty by Date	t	autotable	1
4	Drafter Revenue by Date	t	autotable	1
5	Drafter m3 by Date	t	autotable	1
6	Daily m3 Produced	t	simplegraph	1
\.


--
-- Name: autotable_constraints_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY autotable_constraints
    ADD CONSTRAINT autotable_constraints_pkey PRIMARY KEY (autotable_constraints_id);


--
-- Name: autotable_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY autotable_templates
    ADD CONSTRAINT autotable_templates_pkey PRIMARY KEY (autotable_template_id);


--
-- Name: columns_name_key; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY columns
    ADD CONSTRAINT columns_name_key UNIQUE (name, table_id);


--
-- Name: columns_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY columns
    ADD CONSTRAINT columns_pkey PRIMARY KEY (column_id);


--
-- Name: databases_name_key; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY databases
    ADD CONSTRAINT databases_name_key UNIQUE (name, object_id);


--
-- Name: databases_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY databases
    ADD CONSTRAINT databases_pkey PRIMARY KEY (database_id);


--
-- Name: list_constraints_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY list_constraints
    ADD CONSTRAINT list_constraints_pkey PRIMARY KEY (list_constraints_id);


--
-- Name: list_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY list_templates
    ADD CONSTRAINT list_templates_pkey PRIMARY KEY (list_template_id);


--
-- Name: modules_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (module_id);


--
-- Name: objects_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY objects
    ADD CONSTRAINT objects_pkey PRIMARY KEY (object_id);


--
-- Name: people_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY people
    ADD CONSTRAINT people_pkey PRIMARY KEY (person_id);


--
-- Name: permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (permission_id);


--
-- Name: simplegraph_constraints_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY simplegraph_constraints
    ADD CONSTRAINT simplegraph_constraints_pkey PRIMARY KEY (autotable_constraints_id);


--
-- Name: simplegraph_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY simplegraph_templates
    ADD CONSTRAINT simplegraph_templates_pkey PRIMARY KEY (autotable_template_id);


--
-- Name: table_joins_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY table_joins
    ADD CONSTRAINT table_joins_pkey PRIMARY KEY (table_join_id);


--
-- Name: table_joins_table1_key; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY table_joins
    ADD CONSTRAINT table_joins_table1_key UNIQUE (table1, table2, method);


--
-- Name: tables_name_key; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY tables
    ADD CONSTRAINT tables_name_key UNIQUE (name, database_id);


--
-- Name: tables_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY tables
    ADD CONSTRAINT tables_pkey PRIMARY KEY (table_id);


--
-- Name: templates_pkey; Type: CONSTRAINT; Schema: public; Owner: evan; Tablespace: 
--

ALTER TABLE ONLY templates
    ADD CONSTRAINT templates_pkey PRIMARY KEY (template_id);


--
-- Name: autotable_constraints_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY autotable_constraints
    ADD CONSTRAINT autotable_constraints_column_id_fkey FOREIGN KEY (column_id) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: autotable_constraints_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY autotable_constraints
    ADD CONSTRAINT autotable_constraints_template_id_fkey FOREIGN KEY (template_id) REFERENCES templates(template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: autotable_templates_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY autotable_templates
    ADD CONSTRAINT autotable_templates_column_id_fkey FOREIGN KEY (column_id) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: autotable_templates_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY autotable_templates
    ADD CONSTRAINT autotable_templates_template_id_fkey FOREIGN KEY (template_id) REFERENCES templates(template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: columns_modified_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY columns
    ADD CONSTRAINT columns_modified_by_fkey FOREIGN KEY (modified_by) REFERENCES people(person_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: columns_references_column_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY columns
    ADD CONSTRAINT columns_references_column_fkey FOREIGN KEY (references_column) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: columns_table_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY columns
    ADD CONSTRAINT columns_table_id_fkey FOREIGN KEY (table_id) REFERENCES tables(table_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: databases_modified_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY databases
    ADD CONSTRAINT databases_modified_by_fkey FOREIGN KEY (modified_by) REFERENCES people(person_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: databases_object_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY databases
    ADD CONSTRAINT databases_object_id_fkey FOREIGN KEY (object_id) REFERENCES objects(object_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: list_constraints_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY list_constraints
    ADD CONSTRAINT list_constraints_column_id_fkey FOREIGN KEY (column_id) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: list_constraints_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY list_constraints
    ADD CONSTRAINT list_constraints_template_id_fkey FOREIGN KEY (template_id) REFERENCES templates(template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: list_templates_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY list_templates
    ADD CONSTRAINT list_templates_column_id_fkey FOREIGN KEY (column_id) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: list_templates_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY list_templates
    ADD CONSTRAINT list_templates_template_id_fkey FOREIGN KEY (template_id) REFERENCES templates(template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: objects_modified_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY objects
    ADD CONSTRAINT objects_modified_by_fkey FOREIGN KEY (modified_by) REFERENCES people(person_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: objects_module_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY objects
    ADD CONSTRAINT objects_module_id_fkey FOREIGN KEY (module_id) REFERENCES modules(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: permissions_modified_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY permissions
    ADD CONSTRAINT permissions_modified_by_fkey FOREIGN KEY (modified_by) REFERENCES people(person_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: permissions_person_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY permissions
    ADD CONSTRAINT permissions_person_id_fkey FOREIGN KEY (person_id) REFERENCES people(person_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: simplegraph_constraints_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY simplegraph_constraints
    ADD CONSTRAINT simplegraph_constraints_column_id_fkey FOREIGN KEY (column_id) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: simplegraph_constraints_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY simplegraph_constraints
    ADD CONSTRAINT simplegraph_constraints_template_id_fkey FOREIGN KEY (template_id) REFERENCES templates(template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: simplegraph_templates_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY simplegraph_templates
    ADD CONSTRAINT simplegraph_templates_column_id_fkey FOREIGN KEY (column_id) REFERENCES columns(column_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: simplegraph_templates_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY simplegraph_templates
    ADD CONSTRAINT simplegraph_templates_template_id_fkey FOREIGN KEY (template_id) REFERENCES templates(template_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: table_joins_table1_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY table_joins
    ADD CONSTRAINT table_joins_table1_fkey FOREIGN KEY (table1) REFERENCES tables(table_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: table_joins_table2_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY table_joins
    ADD CONSTRAINT table_joins_table2_fkey FOREIGN KEY (table2) REFERENCES tables(table_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tables_database_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY tables
    ADD CONSTRAINT tables_database_id_fkey FOREIGN KEY (database_id) REFERENCES databases(database_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tables_modified_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY tables
    ADD CONSTRAINT tables_modified_by_fkey FOREIGN KEY (modified_by) REFERENCES people(person_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: templates_object_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: evan
--

ALTER TABLE ONLY templates
    ADD CONSTRAINT templates_object_id_fkey FOREIGN KEY (object_id) REFERENCES objects(object_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

