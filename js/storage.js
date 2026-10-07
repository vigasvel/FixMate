/**
 * FIXMATE - Storage Management Layer
 * "Smart Complaint & Maintenance Management System"
 * 
 * Stage 1: LocalStorage Implementation
 * Designed with a clean CRUD interface for seamless replacement with PHP+MySQL API calls in Stage 2.
 */

const STORAGE_KEYS = {
  USERS: 'fixmate_users',
  COMPLAINTS: 'fixmate_complaints',
  TECHNICIANS: 'fixmate_technicians',
  CATEGORIES: 'fixmate_categories',
  NOTIFICATIONS: 'fixmate_notifications',
  FEEDBACK: 'fixmate_feedback',
  CURRENT_USER: 'fixmate_session_user',
  SETTINGS: 'fixmate_settings'
};

// Initial Seed Data Generators
const SEED_USERS = [
  { id: 1, name: "Admin Manager", email: "admin@fixmate.edu", phone: "9876543210", employee_id: "ADM-101", department: "Facility Management", role: "admin", status: "active", created_at: "2026-01-10" },
  { id: 2, name: "Arun Kumar", email: "technician@fixmate.edu", phone: "9876543211", employee_id: "TECH-201", department: "Electrical Maintenance", role: "technician", status: "active", created_at: "2026-01-12" },
  { id: 3, name: "Praveen M", email: "praveen@fixmate.edu", phone: "9876543212", employee_id: "TECH-202", department: "HVAC Engineering", role: "technician", status: "active", created_at: "2026-01-15" },
  { id: 4, name: "Rahul S", email: "rahul@fixmate.edu", phone: "9876543213", employee_id: "TECH-203", department: "Plumbing Services", role: "technician", status: "active", created_at: "2026-01-18" },
  { id: 5, name: "Karthik R", email: "karthik@fixmate.edu", phone: "9876543214", employee_id: "TECH-204", department: "Network & IT Support", role: "technician", status: "active", created_at: "2026-01-20" },
  { id: 6, name: "Kumaresan K", email: "user@fixmate.edu", phone: "9876543220", employee_id: "CS157", department: "Computer Science & Engg", role: "user", status: "active", created_at: "2026-02-01" },
  { id: 7, name: "Dr. Senthil Nathan", email: "senthil@fixmate.edu", phone: "9876543221", employee_id: "FAC-301", department: "Mechanical Engg", role: "user", status: "active", created_at: "2026-02-02" },
  { id: 8, name: "Ananya Sharma", email: "ananya@fixmate.edu", phone: "9876543222", employee_id: "STU-402", department: "Information Technology", role: "user", status: "active", created_at: "2026-02-03" },
  { id: 9, name: "Vikas Raman", email: "vikas@fixmate.edu", phone: "9876543223", employee_id: "STU-403", department: "Electrical & Electronics", role: "user", status: "active", created_at: "2026-02-05" },
  { id: 10, name: "Prof. Meenakshi Sundaram", email: "meenakshi@fixmate.edu", phone: "9876543224", employee_id: "FAC-304", department: "Civil Engineering", role: "user", status: "active", created_at: "2026-02-06" },
  { id: 11, name: "Deepak Raj", email: "deepak@fixmate.edu", phone: "9876543225", employee_id: "ADM-105", department: "Administration & Accounts", role: "user", status: "active", created_at: "2026-02-07" },
  { id: 12, name: "Sneha Patel", email: "sneha@fixmate.edu", phone: "9876543226", employee_id: "STU-406", department: "Biomedical Engg", role: "user", status: "active", created_at: "2026-02-08" },
  { id: 13, name: "Ramesh Babu", email: "ramesh@fixmate.edu", phone: "9876543227", employee_id: "FAC-307", department: "Physics & Chemistry Lab", role: "user", status: "active", created_at: "2026-02-09" },
  { id: 14, name: "Pooja Venkatesh", email: "pooja@fixmate.edu", phone: "9876543228", employee_id: "STU-408", department: "Management Studies", role: "user", status: "active", created_at: "2026-02-10" },
  { id: 15, name: "Gokul Prasad", email: "gokul@fixmate.edu", phone: "9876543229", employee_id: "FAC-309", department: "Library & Information Science", role: "user", status: "active", created_at: "2026-02-11" },
  { id: 16, name: "Kavitha Mohan", email: "kavitha@fixmate.edu", phone: "9876543230", employee_id: "ADM-110", department: "Hostel Warden Office", role: "user", status: "inactive", created_at: "2026-02-12" }
];

const SEED_TECHNICIANS = [
  { id: 1, name: "Arun Kumar", email: "arun.tech@fixmate.edu", phone: "9876543211", specialization: "Electrical", availability: "Available", active_tasks: 2, completed_tasks: 38, rating: 4.8, status: "active" },
  { id: 2, name: "Praveen M", email: "praveen.tech@fixmate.edu", phone: "9876543212", specialization: "HVAC", availability: "Busy", active_tasks: 4, completed_tasks: 42, rating: 4.9, status: "active" },
  { id: 3, name: "Rahul S", email: "rahul.tech@fixmate.edu", phone: "9876543213", specialization: "Plumbing", availability: "Available", active_tasks: 1, completed_tasks: 29, rating: 4.6, status: "active" },
  { id: 4, name: "Karthik R", email: "karthik.tech@fixmate.edu", phone: "9876543214", specialization: "Networking", availability: "Available", active_tasks: 2, completed_tasks: 51, rating: 4.9, status: "active" },
  { id: 5, name: "Sanjay P", email: "sanjay.tech@fixmate.edu", phone: "9876543215", specialization: "Furniture", availability: "Busy", active_tasks: 3, completed_tasks: 24, rating: 4.5, status: "active" },
  { id: 6, name: "Manoj V", email: "manoj.tech@fixmate.edu", phone: "9876543216", specialization: "Cleaning", availability: "Available", active_tasks: 1, completed_tasks: 60, rating: 4.7, status: "active" },
  { id: 7, name: "Murugan T", email: "murugan.tech@fixmate.edu", phone: "9876543217", specialization: "Infrastructure", availability: "Available", active_tasks: 1, completed_tasks: 31, rating: 4.8, status: "active" },
  { id: 8, name: "Saravanan K", email: "saravanan.tech@fixmate.edu", phone: "9876543218", specialization: "General Maintenance", availability: "On Leave", active_tasks: 0, completed_tasks: 19, rating: 4.4, status: "inactive" }
];

const SEED_CATEGORIES = [
  { id: 1, name: "Electrical", icon: "fa-bolt", description: "Power cuts, switchboards, lights, wiring & breakers", status: "active" },
  { id: 2, name: "Plumbing", icon: "fa-faucet", description: "Pipe leaks, drainage, washroom fixtures & water supply", status: "active" },
  { id: 3, name: "Internet / Network", icon: "fa-wifi", description: "Wi-Fi access points, LAN cables, routers & servers", status: "active" },
  { id: 4, name: "Furniture", icon: "fa-chair", description: "Desks, benches, chairs, door locks & whiteboards", status: "active" },
  { id: 5, name: "Air Conditioning", icon: "fa-snowflake", description: "AC cooling issues, compressor faults, thermostat & filters", status: "active" },
  { id: 6, name: "Cleaning", icon: "fa-broom", description: "Waste disposal, floor wash, sanitation & hygiene", status: "active" },
  { id: 7, name: "Infrastructure", icon: "fa-building", description: "Ceiling dampness, window panes, paint & civil repair", status: "active" },
  { id: 8, name: "Other", icon: "fa-tools", description: "General facility maintenance & miscellaneous requirements", status: "active" }
];

const SEED_COMPLAINTS = [
  {
    id: 1,
    complaint_code: "FM-2026-0001",
    user_id: 6,
    user_name: "Kumaresan K",
    user_dept: "Computer Science & Engg",
    user_phone: "9876543220",
    category_id: 5,
    category_name: "Air Conditioning",
    title: "Split AC not cooling in Computer Lab 3",
    description: "The 2.0 ton split AC in Computer Lab 3 is blowing warm air and making a loud rattling sound. Lab temperature is rising.",
    location: "Block A - 3rd Floor",
    building: "Block A",
    floor: "3rd Floor",
    room_number: "Room 302",
    priority: "HIGH",
    status: "IN PROGRESS",
    technician_id: 2,
    technician_name: "Praveen M",
    submitted_date: "2026-03-01 09:30 AM",
    assigned_date: "2026-03-01 10:15 AM",
    started_date: "2026-03-01 11:45 AM",
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-01",
    contact_number: "9876543220",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "09:30 AM, 01 Mar 2026", note: "Complaint logged by Kumaresan K" },
      { status: "ASSIGNED", time: "10:15 AM, 01 Mar 2026", note: "Assigned to HVAC specialist Praveen M by Admin" },
      { status: "IN PROGRESS", time: "11:45 AM, 01 Mar 2026", note: "Praveen M inspected compressor and refilling gas" }
    ],
    comments: [
      { id: 1, user_name: "Kumaresan K", user_role: "user", comment: "We have an ongoing practical exam at 2 PM, please expedite.", time: "10:30 AM" },
      { id: 2, user_name: "Praveen M", user_role: "technician", comment: "On-site now. Replacing the capacitor and testing cooling.", time: "11:50 AM" }
    ]
  },
  {
    id: 2,
    complaint_code: "FM-2026-0002",
    user_id: 6,
    user_name: "Kumaresan K",
    user_dept: "Computer Science & Engg",
    user_phone: "9876543220",
    category_id: 3,
    category_name: "Internet / Network",
    title: "Wi-Fi access point offline near Seminar Hall",
    description: "The ceiling mounted Cisco Access Point is showing red LED and students are losing connectivity during presentations.",
    location: "Seminar Hall - 1st Floor",
    building: "Seminar Hall",
    floor: "1st Floor",
    room_number: "SH-101",
    priority: "CRITICAL",
    status: "ASSIGNED",
    technician_id: 4,
    technician_name: "Karthik R",
    submitted_date: "2026-03-02 08:45 AM",
    assigned_date: "2026-03-02 09:00 AM",
    started_date: null,
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-02",
    contact_number: "9876543220",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "08:45 AM, 02 Mar 2026", note: "Critical network ticket created" },
      { status: "ASSIGNED", time: "09:00 AM, 02 Mar 2026", note: "Assigned to Karthik R" }
    ],
    comments: []
  },
  {
    id: 3,
    complaint_code: "FM-2026-0003",
    user_id: 6,
    user_name: "Kumaresan K",
    user_dept: "Computer Science & Engg",
    user_phone: "9876543220",
    category_id: 1,
    category_name: "Electrical",
    title: "Tube light flickering and spark in switchboard",
    description: "Two tube lights are flickering constantly and burning smell coming from main switchboard in Classroom 204.",
    location: "Block B - 2nd Floor",
    building: "Block B",
    floor: "2nd Floor",
    room_number: "Room 204",
    priority: "HIGH",
    status: "RESOLVED",
    technician_id: 1,
    technician_name: "Arun Kumar",
    submitted_date: "2026-02-28 10:00 AM",
    assigned_date: "2026-02-28 10:30 AM",
    started_date: "2026-02-28 11:00 AM",
    resolved_date: "2026-02-28 01:15 PM",
    closed_date: null,
    preferred_date: "2026-02-28",
    contact_number: "9876543220",
    resolution_notes: "Replaced 32A MCB breaker switch and 2 electronic ballasts. Tested load stability.",
    parts_used: "1x Schneider 32A MCB, 2x Philips 36W Ballast",
    timeline: [
      { status: "SUBMITTED", time: "10:00 AM, 28 Feb 2026", note: "Complaint logged" },
      { status: "ASSIGNED", time: "10:30 AM, 28 Feb 2026", note: "Assigned to Arun Kumar" },
      { status: "IN PROGRESS", time: "11:00 AM, 28 Feb 2026", note: "Electrical repair in progress" },
      { status: "RESOLVED", time: "01:15 PM, 28 Feb 2026", note: "Repairs completed and tested" }
    ],
    comments: [
      { id: 1, user_name: "Arun Kumar", user_role: "technician", comment: "Replaced faulty switchboard breaker. Please verify.", time: "01:15 PM" }
    ]
  },
  {
    id: 4,
    complaint_code: "FM-2026-0004",
    user_id: 6,
    user_name: "Kumaresan K",
    user_dept: "Computer Science & Engg",
    user_phone: "9876543220",
    category_id: 2,
    category_name: "Plumbing",
    title: "Continuous water leakage from tap in washroom",
    description: "Tap valve damaged on 2nd floor male washroom causing continuous wastage of water.",
    location: "Block A - 2nd Floor",
    building: "Block A",
    floor: "2nd Floor",
    room_number: "Washroom A-2",
    priority: "MEDIUM",
    status: "CLOSED",
    technician_id: 3,
    technician_name: "Rahul S",
    submitted_date: "2026-02-25 09:10 AM",
    assigned_date: "2026-02-25 09:40 AM",
    started_date: "2026-02-25 10:15 AM",
    resolved_date: "2026-02-25 11:30 AM",
    closed_date: "2026-02-25 04:00 PM",
    preferred_date: "2026-02-25",
    contact_number: "9876543220",
    resolution_notes: "Replaced ceramic disc cartridge spindle valve and Teflon tape seal.",
    parts_used: "1x Brass Spindle Valve 1/2 inch",
    timeline: [
      { status: "SUBMITTED", time: "09:10 AM, 25 Feb 2026", note: "Ticket created" },
      { status: "ASSIGNED", time: "09:40 AM, 25 Feb 2026", note: "Assigned to Rahul S" },
      { status: "IN PROGRESS", time: "10:15 AM, 25 Feb 2026", note: "Plumbing repair underway" },
      { status: "RESOLVED", time: "11:30 AM, 25 Feb 2026", note: "Leak fixed" },
      { status: "CLOSED", time: "04:00 PM, 25 Feb 2026", note: "User verified and rated 5-stars" }
    ],
    comments: []
  },
  {
    id: 5,
    complaint_code: "FM-2026-0005",
    user_id: 8,
    user_name: "Ananya Sharma",
    user_dept: "Information Technology",
    user_phone: "9876543222",
    category_id: 4,
    category_name: "Furniture",
    title: "Broken armrest and loose screws on 5 classroom chairs",
    description: "Desk chairs in Lecture Hall 105 have loose bolts and two armrests are detached posing injury risk.",
    location: "Block C - 1st Floor",
    building: "Block C",
    floor: "1st Floor",
    room_number: "Room 105",
    priority: "LOW",
    status: "SUBMITTED",
    technician_id: null,
    technician_name: "Unassigned",
    submitted_date: "2026-03-02 11:20 AM",
    assigned_date: null,
    started_date: null,
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-03",
    contact_number: "9876543222",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "11:20 AM, 02 Mar 2026", note: "Complaint logged by Ananya Sharma" }
    ],
    comments: []
  },
  {
    id: 6,
    complaint_code: "FM-2026-0006",
    user_id: 7,
    user_name: "Dr. Senthil Nathan",
    user_dept: "Mechanical Engg",
    user_phone: "9876543221",
    category_id: 6,
    category_name: "Cleaning",
    title: "Water stagnation and waste accumulation outside Workshop",
    description: "Drainage overflow near thermal workshop exit creating foul odor and hygiene hazard.",
    location: "Block B - Ground Floor",
    building: "Block B",
    floor: "Ground Floor",
    room_number: "Mech Workshop",
    priority: "HIGH",
    status: "IN PROGRESS",
    technician_id: 6,
    technician_name: "Manoj V",
    submitted_date: "2026-03-01 02:00 PM",
    assigned_date: "2026-03-01 02:30 PM",
    started_date: "2026-03-01 03:00 PM",
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-01",
    contact_number: "9876543221",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "02:00 PM, 01 Mar 2026", note: "Ticket created" },
      { status: "ASSIGNED", time: "02:30 PM, 01 Mar 2026", note: "Assigned to Manoj V" },
      { status: "IN PROGRESS", time: "03:00 PM, 01 Mar 2026", note: "Deep sanitation and clearing in progress" }
    ],
    comments: []
  },
  {
    id: 7,
    complaint_code: "FM-2026-0007",
    user_id: 10,
    user_name: "Prof. Meenakshi Sundaram",
    user_dept: "Civil Engineering",
    user_phone: "9876543224",
    category_id: 7,
    category_name: "Infrastructure",
    title: "Ceiling plaster peeling and rain water seepage",
    description: "Ceiling paint and plaster falling off near faculty cubicles due to terrace drainage block.",
    location: "Administrative Block - 2nd Floor",
    building: "Administrative Block",
    floor: "2nd Floor",
    room_number: "Staff Room 210",
    priority: "CRITICAL",
    status: "IN PROGRESS",
    technician_id: 7,
    technician_name: "Murugan T",
    submitted_date: "2026-02-27 08:30 AM",
    assigned_date: "2026-02-27 09:15 AM",
    started_date: "2026-02-27 10:00 AM",
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-02-27",
    contact_number: "9876543224",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "08:30 AM, 27 Feb 2026", note: "Critical infrastructure issue logged" },
      { status: "ASSIGNED", time: "09:15 AM, 27 Feb 2026", note: "Assigned to Murugan T" },
      { status: "IN PROGRESS", time: "10:00 AM, 27 Feb 2026", note: "Terrace waterproofing and plastering started" }
    ],
    comments: []
  },
  {
    id: 8,
    complaint_code: "FM-2026-0008",
    user_id: 9,
    user_name: "Vikas Raman",
    user_dept: "Electrical & Electronics",
    user_phone: "9876543223",
    category_id: 1,
    category_name: "Electrical",
    title: "Power socket tripping whenever project kit is plugged",
    description: "Workbench socket 4 in Embedded Systems Lab keeps tripping the MCB.",
    location: "Block A - 1st Floor",
    building: "Block A",
    floor: "1st Floor",
    room_number: "Lab 104",
    priority: "MEDIUM",
    status: "RESOLVED",
    technician_id: 1,
    technician_name: "Arun Kumar",
    submitted_date: "2026-02-26 11:00 AM",
    assigned_date: "2026-02-26 11:30 AM",
    started_date: "2026-02-26 01:00 PM",
    resolved_date: "2026-02-26 03:30 PM",
    closed_date: null,
    preferred_date: "2026-02-26",
    contact_number: "9876543223",
    resolution_notes: "Neutral ground short found in socket wiring. Rewired and replaced 16A shutter socket.",
    parts_used: "1x Anchor 16A Modular Socket",
    timeline: [
      { status: "SUBMITTED", time: "11:00 AM, 26 Feb 2026", note: "Ticket created" },
      { status: "ASSIGNED", time: "11:30 AM, 26 Feb 2026", note: "Assigned to Arun Kumar" },
      { status: "IN PROGRESS", time: "01:00 PM, 26 Feb 2026", note: "Socket inspection" },
      { status: "RESOLVED", time: "03:30 PM, 26 Feb 2026", note: "Fixed wiring issue" }
    ],
    comments: []
  },
  {
    id: 9,
    complaint_code: "FM-2026-0009",
    user_id: 15,
    user_name: "Gokul Prasad",
    user_dept: "Library & Information Science",
    user_phone: "9876543229",
    category_id: 3,
    category_name: "Internet / Network",
    title: "LAN ports not detecting network on Reference Section PCs",
    description: "Row 3 and Row 4 digital library computers cannot reach OPAC catalog server.",
    location: "Central Library - 2nd Floor",
    building: "Library",
    floor: "2nd Floor",
    room_number: "Digital Section",
    priority: "HIGH",
    status: "ASSIGNED",
    technician_id: 4,
    technician_name: "Karthik R",
    submitted_date: "2026-03-02 10:00 AM",
    assigned_date: "2026-03-02 10:30 AM",
    started_date: null,
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-02",
    contact_number: "9876543229",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "10:00 AM, 02 Mar 2026", note: "Ticket submitted" },
      { status: "ASSIGNED", time: "10:30 AM, 02 Mar 2026", note: "Assigned to Karthik R" }
    ],
    comments: []
  },
  {
    id: 10,
    complaint_code: "FM-2026-0010",
    user_id: 11,
    user_name: "Deepak Raj",
    user_dept: "Administration & Accounts",
    user_phone: "9876543225",
    category_id: 5,
    category_name: "Air Conditioning",
    title: "Water dripping from indoor cassette AC unit",
    description: "Condensation line blocked, water dripping onto file cabinets.",
    location: "Administrative Block - 1st Floor",
    building: "Administrative Block",
    floor: "1st Floor",
    room_number: "Finance Office 108",
    priority: "MEDIUM",
    status: "SUBMITTED",
    technician_id: null,
    technician_name: "Unassigned",
    submitted_date: "2026-03-02 01:15 PM",
    assigned_date: null,
    started_date: null,
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-02",
    contact_number: "9876543225",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "01:15 PM, 02 Mar 2026", note: "Logged by Deepak Raj" }
    ],
    comments: []
  },
  {
    id: 11,
    complaint_code: "FM-2026-0011",
    user_id: 13,
    user_name: "Ramesh Babu",
    user_dept: "Physics & Chemistry Lab",
    user_phone: "9876543227",
    category_id: 2,
    category_name: "Plumbing",
    title: "Sink drain choked in Analytical Chemistry Lab",
    description: "Chemical sink basin is backed up with water and not draining.",
    location: "Block C - Ground Floor",
    building: "Block C",
    floor: "Ground Floor",
    room_number: "Chem Lab 02",
    priority: "HIGH",
    status: "RESOLVED",
    technician_id: 3,
    technician_name: "Rahul S",
    submitted_date: "2026-02-24 09:30 AM",
    assigned_date: "2026-02-24 10:00 AM",
    started_date: "2026-02-24 11:00 AM",
    resolved_date: "2026-02-24 01:00 PM",
    closed_date: "2026-02-25 09:00 AM",
    preferred_date: "2026-02-24",
    contact_number: "9876543227",
    resolution_notes: "Cleared sediment blockage in u-trap siphon pipe. Flushed with drain cleaner.",
    parts_used: "1x PVC Waste Pipe 40mm",
    timeline: [
      { status: "SUBMITTED", time: "09:30 AM, 24 Feb 2026", note: "Ticket created" },
      { status: "ASSIGNED", time: "10:00 AM, 24 Feb 2026", note: "Assigned to Rahul S" },
      { status: "IN PROGRESS", time: "11:00 AM, 24 Feb 2026", note: "Clearing blockage" },
      { status: "RESOLVED", time: "01:00 PM, 24 Feb 2026", note: "Drain cleared" },
      { status: "CLOSED", time: "09:00 AM, 25 Feb 2026", note: "User closed ticket" }
    ],
    comments: []
  },
  {
    id: 12,
    complaint_code: "FM-2026-0012",
    user_id: 14,
    user_name: "Pooja Venkatesh",
    user_dept: "Management Studies",
    user_phone: "9876543228",
    category_id: 4,
    category_name: "Furniture",
    title: "Conference room main table leg wobbly and veneer scratched",
    description: "The 16-seater conference table has unstable middle support leg.",
    location: "Administrative Block - 3rd Floor",
    building: "Administrative Block",
    floor: "3rd Floor",
    room_number: "Board Room 301",
    priority: "LOW",
    status: "IN PROGRESS",
    technician_id: 5,
    technician_name: "Sanjay P",
    submitted_date: "2026-02-28 03:00 PM",
    assigned_date: "2026-03-01 09:00 AM",
    started_date: "2026-03-01 10:30 AM",
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-01",
    contact_number: "9876543228",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "03:00 PM, 28 Feb 2026", note: "Logged" },
      { status: "ASSIGNED", time: "09:00 AM, 01 Mar 2026", note: "Assigned to Sanjay P" },
      { status: "IN PROGRESS", time: "10:30 AM, 01 Mar 2026", note: "Carpentry reinforcement" }
    ],
    comments: []
  },
  {
    id: 13,
    complaint_code: "FM-2026-0013",
    user_id: 12,
    user_name: "Sneha Patel",
    user_dept: "Biomedical Engg",
    user_phone: "9876543226",
    category_id: 8,
    category_name: "Other",
    title: "Fire Extinguisher pressure gauge reading below normal zone",
    description: "CO2 / ABC powder fire extinguisher in corridor has lost pressure.",
    location: "Block B - 3rd Floor",
    building: "Block B",
    floor: "3rd Floor",
    room_number: "Corridor B-3",
    priority: "CRITICAL",
    status: "ASSIGNED",
    technician_id: 7,
    technician_name: "Murugan T",
    submitted_date: "2026-03-02 07:30 AM",
    assigned_date: "2026-03-02 08:00 AM",
    started_date: null,
    resolved_date: null,
    closed_date: null,
    preferred_date: "2026-03-02",
    contact_number: "9876543226",
    resolution_notes: "",
    parts_used: "",
    timeline: [
      { status: "SUBMITTED", time: "07:30 AM, 02 Mar 2026", note: "Safety inspection issue reported" },
      { status: "ASSIGNED", time: "08:00 AM, 02 Mar 2026", note: "Assigned to Murugan T" }
    ],
    comments: []
  },
  {
    id: 14,
    complaint_code: "FM-2026-0014",
    user_id: 7,
    user_name: "Dr. Senthil Nathan",
    user_dept: "Mechanical Engg",
    user_phone: "9876543221",
    category_id: 1,
    category_name: "Electrical",
    title: "Exhaust fan not functioning in CAD/CAM Lab",
    description: "Heat buildup in computer lab due to non-functioning wall exhaust.",
    location: "Block A - Ground Floor",
    building: "Block A",
    floor: "Ground Floor",
    room_number: "CAD Lab 04",
    priority: "MEDIUM",
    status: "RESOLVED",
    technician_id: 1,
    technician_name: "Arun Kumar",
    submitted_date: "2026-02-23 10:00 AM",
    assigned_date: "2026-02-23 10:45 AM",
    started_date: "2026-02-23 02:00 PM",
    resolved_date: "2026-02-23 04:30 PM",
    closed_date: "2026-02-24 10:00 AM",
    preferred_date: "2026-02-23",
    contact_number: "9876543221",
    resolution_notes: "Replaced 45W heavy duty motor and lubricated bearings.",
    parts_used: "1x Crompton Exhaust Motor 300mm",
    timeline: [
      { status: "SUBMITTED", time: "10:00 AM, 23 Feb 2026", note: "Logged" },
      { status: "ASSIGNED", time: "10:45 AM, 23 Feb 2026", note: "Assigned to Arun Kumar" },
      { status: "IN PROGRESS", time: "02:00 PM, 23 Feb 2026", note: "Motor replacement" },
      { status: "RESOLVED", time: "04:30 PM, 23 Feb 2026", note: "Exhaust working" },
      { status: "CLOSED", time: "10:00 AM, 24 Feb 2026", note: "Feedback rated 5/5" }
    ],
    comments: []
  },
  {
    id: 15,
    complaint_code: "FM-2026-0015",
    user_id: 6,
    user_name: "Kumaresan K",
    user_dept: "Computer Science & Engg",
    user_phone: "9876543220",
    category_id: 6,
    category_name: "Cleaning",
    title: "Post-event cleaning required in Auditorium",
    description: "Auditorium seating area and stage requires thorough trash cleanup and vacuuming after workshop.",
    location: "Auditorium - Ground Floor",
    building: "Auditorium",
    floor: "Ground Floor",
    room_number: "Main Hall",
    priority: "MEDIUM",
    status: "CLOSED",
    technician_id: 6,
    technician_name: "Manoj V",
    submitted_date: "2026-02-20 05:00 PM",
    assigned_date: "2026-02-21 08:30 AM",
    started_date: "2026-02-21 09:00 AM",
    resolved_date: "2026-02-21 11:30 AM",
    closed_date: "2026-02-21 01:00 PM",
    preferred_date: "2026-02-21",
    contact_number: "9876543220",
    resolution_notes: "Full deep clean completed, trash disposed, stage sanitized.",
    parts_used: "Sanitation supplies",
    timeline: [
      { status: "SUBMITTED", time: "05:00 PM, 20 Feb 2026", note: "Requested" },
      { status: "ASSIGNED", time: "08:30 AM, 21 Feb 2026", note: "Assigned" },
      { status: "IN PROGRESS", time: "09:00 AM, 21 Feb 2026", note: "Cleaning team deployed" },
      { status: "RESOLVED", time: "11:30 AM, 21 Feb 2026", note: "Done" },
      { status: "CLOSED", time: "01:00 PM, 21 Feb 2026", note: "Closed by user" }
    ],
    comments: []
  }
];

// Generate extra realistic seed complaints to reach 25 items
const EXTRA_COMPLAINTS_CONFIG = [
  { code: "FM-2026-0016", cat: 5, catName: "Air Conditioning", user: 8, userName: "Ananya Sharma", uDept: "IT", title: "AC remote display blank in Faculty Room 12", loc: "Block A - 2nd Floor", room: "Room 212", bldg: "Block A", fl: "2nd Floor", pri: "LOW", stat: "RESOLVED", techId: 2, techName: "Praveen M", date: "2026-02-18" },
  { code: "FM-2026-0017", cat: 1, catName: "Electrical", user: 9, userName: "Vikas Raman", uDept: "EEE", title: "Corridor light fixture fell from ceiling hook", loc: "Block C - 2nd Floor", room: "Corridor 2B", bldg: "Block C", fl: "2nd Floor", pri: "CRITICAL", stat: "CLOSED", techId: 1, techName: "Arun Kumar", date: "2026-02-15" },
  { code: "FM-2026-0018", cat: 3, catName: "Internet / Network", user: 7, userName: "Dr. Senthil Nathan", uDept: "Mech", title: "Network latency spiking over 500ms on Faculty subnet", loc: "Block B - 1st Floor", room: "HOD Office", bldg: "Block B", fl: "1st Floor", pri: "MEDIUM", stat: "RESOLVED", techId: 4, techName: "Karthik R", date: "2026-02-14" },
  { code: "FM-2026-0019", cat: 7, catName: "Infrastructure", user: 11, userName: "Deepak Raj", uDept: "Admin", title: "Glass door closer leaking hydraulic fluid", loc: "Admin Block - Ground Floor", room: "Entrance Foyer", bldg: "Administrative Block", fl: "Ground Floor", pri: "LOW", stat: "CLOSED", techId: 7, techName: "Murugan T", date: "2026-02-12" },
  { code: "FM-2026-0020", cat: 2, catName: "Plumbing", user: 13, userName: "Ramesh Babu", uDept: "Science", title: "Overhead tank float valve sticking open", loc: "Hostel Block A - Terrace", room: "Roof Tank", bldg: "Hostel", fl: "Terrace", pri: "HIGH", stat: "CLOSED", techId: 3, techName: "Rahul S", date: "2026-02-10" },
  { code: "FM-2026-0021", cat: 4, catName: "Furniture", user: 14, userName: "Pooja Venkatesh", uDept: "MBA", title: "Podium mic holder screw thread stripped", loc: "Seminar Hall - Ground Floor", room: "Stage", bldg: "Seminar Hall", fl: "Ground Floor", pri: "LOW", stat: "RESOLVED", techId: 5, techName: "Sanjay P", date: "2026-02-08" },
  { code: "FM-2026-0022", cat: 5, catName: "Air Conditioning", user: 6, userName: "Kumaresan K", uDept: "CSE", title: "Server room precision AC unit displaying E-04 error", loc: "Block A - 3rd Floor", room: "Server Room", bldg: "Block A", fl: "3rd Floor", pri: "CRITICAL", stat: "CLOSED", techId: 2, techName: "Praveen M", date: "2026-02-05" },
  { code: "FM-2026-0023", cat: 1, catName: "Electrical", user: 15, userName: "Gokul Prasad", uDept: "Library", title: "Emergency exit light battery backup dead", loc: "Library - 1st Floor", room: "Staircase A", bldg: "Library", fl: "1st Floor", pri: "MEDIUM", stat: "CLOSED", techId: 1, techName: "Arun Kumar", date: "2026-02-03" },
  { code: "FM-2026-0024", cat: 6, catName: "Cleaning", user: 8, userName: "Ananya Sharma", uDept: "IT", title: "Spilled liquid coolant in Hardware lab", loc: "Block B - 2nd Floor", room: "Lab 206", bldg: "Block B", fl: "2nd Floor", pri: "MEDIUM", stat: "CLOSED", techId: 6, techName: "Manoj V", date: "2026-02-01" },
  { code: "FM-2026-0025", cat: 7, catName: "Infrastructure", user: 10, userName: "Prof. Meenakshi Sundaram", uDept: "Civil", title: "Window latch broken allowing dust during storms", loc: "Block C - 3rd Floor", room: "Room 310", bldg: "Block C", fl: "3rd Floor", pri: "LOW", stat: "CLOSED", techId: 7, techName: "Murugan T", date: "2026-01-28" }
];

EXTRA_COMPLAINTS_CONFIG.forEach((c, idx) => {
  SEED_COMPLAINTS.push({
    id: 16 + idx,
    complaint_code: c.code,
    user_id: c.user,
    user_name: c.userName,
    user_dept: c.uDept,
    user_phone: "9876543200",
    category_id: c.cat,
    category_name: c.catName,
    title: c.title,
    description: `Detailed facility requirement regarding ${c.title.toLowerCase()} in ${c.loc}. Inspected by facility maintenance.`,
    location: c.loc,
    building: c.bldg,
    floor: c.fl,
    room_number: c.room,
    priority: c.pri,
    status: c.stat,
    technician_id: c.techId,
    technician_name: c.techName,
    submitted_date: `${c.date} 09:00 AM`,
    assigned_date: `${c.date} 09:30 AM`,
    started_date: `${c.date} 10:00 AM`,
    resolved_date: `${c.date} 02:00 PM`,
    closed_date: c.stat === "CLOSED" ? `${c.date} 04:00 PM` : null,
    preferred_date: c.date,
    contact_number: "9876543200",
    resolution_notes: "Inspected, components repaired/replaced, functional test verified successfully.",
    parts_used: "Standard replacement parts and consumables",
    timeline: [
      { status: "SUBMITTED", time: `09:00 AM, ${c.date}`, note: "Ticket logged" },
      { status: "ASSIGNED", time: `09:30 AM, ${c.date}`, note: `Assigned to ${c.techName}` },
      { status: "IN PROGRESS", time: `10:00 AM, ${c.date}`, note: "Work started" },
      { status: "RESOLVED", time: `02:00 PM, ${c.date}`, note: "Work completed" },
      ...(c.stat === "CLOSED" ? [{ status: "CLOSED", time: `04:00 PM, ${c.date}`, note: "Confirmed by user" }] : [])
    ],
    comments: []
  });
});

const SEED_NOTIFICATIONS = [
  { id: 1, user_id: 6, complaint_code: "FM-2026-0001", message: "Technician Praveen M started working on your complaint FM-2026-0001.", type: "info", is_read: false, created_at: "2026-03-01 11:45 AM" },
  { id: 2, user_id: 6, complaint_code: "FM-2026-0002", message: "Your critical ticket FM-2026-0002 has been assigned to Karthik R.", type: "warning", is_read: false, created_at: "2026-03-02 09:00 AM" },
  { id: 3, user_id: 6, complaint_code: "FM-2026-0003", message: "Your electrical complaint FM-2026-0003 was marked as RESOLVED. Please provide feedback.", type: "success", is_read: false, created_at: "2026-02-28 01:15 PM" },
  { id: 4, user_id: 1, complaint_code: "FM-2026-0002", message: "CRITICAL: Wi-Fi access point offline near Seminar Hall requires urgent intervention.", type: "danger", is_read: false, created_at: "2026-03-02 08:45 AM" },
  { id: 5, user_id: 1, complaint_code: "FM-2026-0007", message: "CRITICAL: Ceiling plaster peeling and rain water seepage logged by Prof. Meenakshi.", type: "danger", is_read: false, created_at: "2026-02-27 08:30 AM" },
  { id: 6, user_id: 2, complaint_code: "FM-2026-0003", message: "New task assigned: Tube light flickering in Classroom 204.", type: "info", is_read: true, created_at: "2026-02-28 10:30 AM" },
  { id: 7, user_id: 2, complaint_code: "FM-2026-0008", message: "New task assigned: Power socket tripping in Lab 104.", type: "info", is_read: true, created_at: "2026-02-26 11:30 AM" },
  { id: 8, user_id: 6, complaint_code: "FM-2026-0004", message: "Complaint FM-2026-0004 has been closed successfully. Thank you for your feedback!", type: "success", is_read: true, created_at: "2026-02-25 04:00 PM" },
  { id: 9, user_id: 1, complaint_code: "FM-2026-0005", message: "New complaint FM-2026-0005 submitted by Ananya Sharma (IT Dept).", type: "info", is_read: true, created_at: "2026-03-02 11:20 AM" },
  { id: 10, user_id: 1, complaint_code: "FM-2026-0010", message: "New complaint FM-2026-0010 submitted by Deepak Raj (Admin Dept).", type: "info", is_read: true, created_at: "2026-03-02 01:15 PM" },
  { id: 11, user_id: 6, complaint_code: "FM-2026-0001", message: "Praveen M posted a comment on FM-2026-0001: 'On-site now. Replacing capacitor.'", type: "info", is_read: true, created_at: "2026-03-01 11:50 AM" },
  { id: 12, user_id: 7, complaint_code: "FM-2026-0006", message: "Technician Manoj V assigned to cleaning complaint FM-2026-0006.", type: "info", is_read: true, created_at: "2026-03-01 02:30 PM" },
  { id: 13, user_id: 10, complaint_code: "FM-2026-0007", message: "Murugan T started terrace repair work for complaint FM-2026-0007.", type: "info", is_read: true, created_at: "2026-02-27 10:00 AM" },
  { id: 14, user_id: 9, complaint_code: "FM-2026-0008", message: "Complaint FM-2026-0008 resolved by Arun Kumar.", type: "success", is_read: true, created_at: "2026-02-26 03:30 PM" },
  { id: 15, user_id: 15, complaint_code: "FM-2026-0009", message: "Ticket FM-2026-0009 assigned to Network Specialist Karthik R.", type: "info", is_read: true, created_at: "2026-03-02 10:30 AM" },
  { id: 16, user_id: 13, complaint_code: "FM-2026-0011", message: "Sink drain blockage resolved and verified in Chemistry Lab.", type: "success", is_read: true, created_at: "2026-02-24 01:00 PM" },
  { id: 17, user_id: 14, complaint_code: "FM-2026-0012", message: "Sanjay P began repairs on Board Room table.", type: "info", is_read: true, created_at: "2026-03-01 10:30 AM" },
  { id: 18, user_id: 12, complaint_code: "FM-2026-0013", message: "Safety Alert: Fire extinguisher servicing initiated for FM-2026-0013.", type: "warning", is_read: true, created_at: "2026-03-02 08:00 AM" },
  { id: 19, user_id: 7, complaint_code: "FM-2026-0014", message: "CAD Lab exhaust motor replacement completed and tested.", type: "success", is_read: true, created_at: "2026-02-23 04:30 PM" },
  { id: 20, user_id: 6, complaint_code: "FM-2026-0015", message: "Auditorium cleanup task completed on schedule.", type: "success", is_read: true, created_at: "2026-02-21 11:30 AM" }
];

const SEED_FEEDBACK = [
  { id: 1, complaint_id: 4, complaint_code: "FM-2026-0004", user_id: 6, user_name: "Kumaresan K", rating: 5, comment: "Prompt repair within 2 hours. Rahul was courteous and cleaned the area after fixing the tap.", created_at: "2026-02-25 04:00 PM" },
  { id: 2, complaint_id: 11, complaint_code: "FM-2026-0011", user_id: 13, user_name: "Ramesh Babu", rating: 5, comment: "Chemistry lab sink drain cleared effectively without chemical contamination. Great job.", created_at: "2026-02-25 09:00 AM" },
  { id: 3, complaint_id: 14, complaint_code: "FM-2026-0014", user_id: 7, user_name: "Dr. Senthil Nathan", rating: 5, comment: "Exhaust fan motor replaced promptly. Lab ventilation is back to optimal state.", created_at: "2026-02-24 10:00 AM" },
  { id: 4, complaint_id: 15, complaint_code: "FM-2026-0015", user_id: 6, user_name: "Kumaresan K", rating: 4, comment: "Auditorium was spotless before the morning VIP guest lecture.", created_at: "2026-02-21 01:00 PM" },
  { id: 5, complaint_id: 17, complaint_code: "FM-2026-0017", user_id: 9, user_name: "Vikas Raman", rating: 5, comment: "Corridor light fixture re-anchored firmly. Appreciate the swift response to critical safety hazards.", created_at: "2026-02-15 04:30 PM" },
  { id: 6, complaint_id: 18, complaint_code: "FM-2026-0018", user_id: 7, user_name: "Dr. Senthil Nathan", rating: 5, comment: "Network switch rebooted and fiber cable re-terminated. Zero ping drops now.", created_at: "2026-02-14 05:00 PM" },
  { id: 7, complaint_id: 19, complaint_code: "FM-2026-0019", user_id: 11, user_name: "Deepak Raj", rating: 4, comment: "Entrance glass door hydraulic arm replaced, closes smoothly now.", created_at: "2026-02-12 03:00 PM" },
  { id: 8, complaint_id: 20, complaint_code: "FM-2026-0020", user_id: 13, user_name: "Ramesh Babu", rating: 5, comment: "Hostel terrace tank overflow stopped instantly. Excellent plumber work.", created_at: "2026-02-10 06:00 PM" },
  { id: 9, complaint_id: 21, complaint_code: "FM-2026-0021", user_id: 14, user_name: "Pooja Venkatesh", rating: 4, comment: "Podium podium mount re-threaded securely.", created_at: "2026-02-08 04:00 PM" },
  { id: 10, complaint_id: 22, complaint_code: "FM-2026-0022", user_id: 6, user_name: "Kumaresan K", rating: 5, comment: "Server room AC unit fault resolved before any thermal shutdown. Superb work Praveen!", created_at: "2026-02-05 05:30 PM" },
  { id: 11, complaint_id: 23, complaint_code: "FM-2026-0023", user_id: 15, user_name: "Gokul Prasad", rating: 5, comment: "Library emergency lights battery bank restored.", created_at: "2026-02-03 04:00 PM" },
  { id: 12, complaint_id: 24, complaint_code: "FM-2026-0024", user_id: 8, user_name: "Ananya Sharma", rating: 5, comment: "Hardware lab coolant spill sanitized quickly and completely.", created_at: "2026-02-01 04:00 PM" },
  { id: 13, complaint_id: 25, complaint_code: "FM-2026-0025", user_id: 10, user_name: "Prof. Meenakshi Sundaram", rating: 4, comment: "Window frame latches aligned nicely.", created_at: "2026-01-28 05:00 PM" },
  { id: 14, complaint_id: 8, complaint_code: "FM-2026-0008", user_id: 9, user_name: "Vikas Raman", rating: 5, comment: "Lab power socket rewired safely. Tested with high wattage bench loads.", created_at: "2026-02-26 04:00 PM" },
  { id: 15, complaint_id: 3, complaint_code: "FM-2026-0003", user_id: 6, user_name: "Kumaresan K", rating: 5, comment: "Switchboard spark fixed and tube lights replaced with bright LED panels. Excellent!", created_at: "2026-02-28 02:00 PM" }
];

/**
 * Storage Controller Class
 */
class FixMateStorage {
  constructor() {
    this.init();
  }

  init() {
    if (!localStorage.getItem(STORAGE_KEYS.USERS)) {
      localStorage.setItem(STORAGE_KEYS.USERS, JSON.stringify(SEED_USERS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.TECHNICIANS)) {
      localStorage.setItem(STORAGE_KEYS.TECHNICIANS, JSON.stringify(SEED_TECHNICIANS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.CATEGORIES)) {
      localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(SEED_CATEGORIES));
    }
    if (!localStorage.getItem(STORAGE_KEYS.COMPLAINTS)) {
      localStorage.setItem(STORAGE_KEYS.COMPLAINTS, JSON.stringify(SEED_COMPLAINTS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.NOTIFICATIONS)) {
      localStorage.setItem(STORAGE_KEYS.NOTIFICATIONS, JSON.stringify(SEED_NOTIFICATIONS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.FEEDBACK)) {
      localStorage.setItem(STORAGE_KEYS.FEEDBACK, JSON.stringify(SEED_FEEDBACK));
    }
  }

  // --- USERS CRUD ---
  getUsers() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.USERS) || '[]');
  }

  getUserById(id) {
    const users = this.getUsers();
    return users.find(u => u.id === Number(id)) || null;
  }

  getUserByEmail(email) {
    const users = this.getUsers();
    return users.find(u => u.email.toLowerCase() === email.toLowerCase()) || null;
  }

  addUser(user) {
    const users = this.getUsers();
    const newId = users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1;
    const newUser = {
      id: newId,
      name: user.name,
      email: user.email,
      phone: user.phone || "",
      employee_id: user.employee_id || `STU-${100 + newId}`,
      department: user.department || "General",
      role: user.role || "user",
      status: "active",
      created_at: new Date().toISOString().split('T')[0]
    };
    users.push(newUser);
    localStorage.setItem(STORAGE_KEYS.USERS, JSON.stringify(users));
    return newUser;
  }

  updateUser(id, updatedFields) {
    const users = this.getUsers();
    const index = users.findIndex(u => u.id === Number(id));
    if (index !== -1) {
      users[index] = { ...users[index], ...updatedFields };
      localStorage.setItem(STORAGE_KEYS.USERS, JSON.stringify(users));
      return users[index];
    }
    return null;
  }

  deleteUser(id) {
    let users = this.getUsers();
    users = users.filter(u => u.id !== Number(id));
    localStorage.setItem(STORAGE_KEYS.USERS, JSON.stringify(users));
    return true;
  }

  // --- TECHNICIANS CRUD ---
  getTechnicians() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.TECHNICIANS) || '[]');
  }

  getTechnicianById(id) {
    const techs = this.getTechnicians();
    return techs.find(t => t.id === Number(id)) || null;
  }

  addTechnician(tech) {
    const techs = this.getTechnicians();
    const newId = techs.length > 0 ? Math.max(...techs.map(t => t.id)) + 1 : 1;
    const newTech = {
      id: newId,
      name: tech.name,
      email: tech.email,
      phone: tech.phone,
      specialization: tech.specialization,
      availability: tech.availability || "Available",
      active_tasks: 0,
      completed_tasks: 0,
      rating: 5.0,
      status: "active"
    };
    techs.push(newTech);
    localStorage.setItem(STORAGE_KEYS.TECHNICIANS, JSON.stringify(techs));
    return newTech;
  }

  updateTechnician(id, fields) {
    const techs = this.getTechnicians();
    const idx = techs.findIndex(t => t.id === Number(id));
    if (idx !== -1) {
      techs[idx] = { ...techs[idx], ...fields };
      localStorage.setItem(STORAGE_KEYS.TECHNICIANS, JSON.stringify(techs));
      return techs[idx];
    }
    return null;
  }

  deleteTechnician(id) {
    let techs = this.getTechnicians();
    techs = techs.filter(t => t.id !== Number(id));
    localStorage.setItem(STORAGE_KEYS.TECHNICIANS, JSON.stringify(techs));
    return true;
  }

  // --- CATEGORIES CRUD ---
  getCategories() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.CATEGORIES) || '[]');
  }

  addCategory(category) {
    const cats = this.getCategories();
    const newId = cats.length > 0 ? Math.max(...cats.map(c => c.id)) + 1 : 1;
    const newCat = {
      id: newId,
      name: category.name,
      icon: category.icon || "fa-tools",
      description: category.description || "",
      status: "active"
    };
    cats.push(newCat);
    localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(cats));
    return newCat;
  }

  updateCategory(id, fields) {
    const cats = this.getCategories();
    const idx = cats.findIndex(c => c.id === Number(id));
    if (idx !== -1) {
      cats[idx] = { ...cats[idx], ...fields };
      localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(cats));
      return cats[idx];
    }
    return null;
  }

  deleteCategory(id) {
    let cats = this.getCategories();
    cats = cats.filter(c => c.id !== Number(id));
    localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(cats));
    return true;
  }

  // --- COMPLAINTS CRUD ---
  getComplaints() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.COMPLAINTS) || '[]');
  }

  getComplaintById(id) {
    const list = this.getComplaints();
    return list.find(c => c.id === Number(id) || c.complaint_code === id) || null;
  }

  generateNextComplaintCode() {
    const complaints = this.getComplaints();
    const year = new Date().getFullYear();
    const count = complaints.length + 1;
    const padded = String(count).padStart(4, '0');
    return `FM-${year}-${padded}`;
  }

  addComplaint(data) {
    const complaints = this.getComplaints();
    const newId = complaints.length > 0 ? Math.max(...complaints.map(c => c.id)) + 1 : 1;
    const now = new Date();
    const dateFormatted = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                          now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const newComplaint = {
      id: newId,
      complaint_code: this.generateNextComplaintCode(),
      user_id: data.user_id,
      user_name: data.user_name || "Authorized User",
      user_dept: data.user_dept || "Campus General",
      user_phone: data.contact_number || "",
      category_id: Number(data.category_id),
      category_name: data.category_name,
      title: data.title,
      description: data.description,
      location: `${data.building} - ${data.floor}${data.room_number ? ' (' + data.room_number + ')' : ''}`,
      building: data.building,
      floor: data.floor,
      room_number: data.room_number || "",
      priority: (data.priority || 'MEDIUM').toUpperCase(),
      status: "SUBMITTED",
      technician_id: null,
      technician_name: "Unassigned",
      submitted_date: dateFormatted,
      assigned_date: null,
      started_date: null,
      resolved_date: null,
      closed_date: null,
      preferred_date: data.preferred_date || now.toISOString().split('T')[0],
      contact_number: data.contact_number || "",
      image_data: data.image_data || null,
      resolution_notes: "",
      parts_used: "",
      timeline: [
        { status: "SUBMITTED", time: dateFormatted, note: `Complaint logged by ${data.user_name || 'User'}` }
      ],
      comments: []
    };

    complaints.unshift(newComplaint);
    localStorage.setItem(STORAGE_KEYS.COMPLAINTS, JSON.stringify(complaints));

    // Create Notification for Admin
    this.addNotification({
      user_id: 1, // Admin
      complaint_code: newComplaint.complaint_code,
      message: `New ${newComplaint.priority} complaint ${newComplaint.complaint_code} submitted by ${newComplaint.user_name}.`,
      type: newComplaint.priority === 'CRITICAL' ? 'danger' : 'info'
    });

    return newComplaint;
  }

  updateComplaint(id, fields) {
    const complaints = this.getComplaints();
    const idx = complaints.findIndex(c => c.id === Number(id) || c.complaint_code === id);
    if (idx !== -1) {
      complaints[idx] = { ...complaints[idx], ...fields };
      localStorage.setItem(STORAGE_KEYS.COMPLAINTS, JSON.stringify(complaints));
      return complaints[idx];
    }
    return null;
  }

  deleteComplaint(id) {
    let complaints = this.getComplaints();
    complaints = complaints.filter(c => c.id !== Number(id) && c.complaint_code !== id);
    localStorage.setItem(STORAGE_KEYS.COMPLAINTS, JSON.stringify(complaints));
    return true;
  }

  addCommentToComplaint(complaintId, commentObj) {
    const complaint = this.getComplaintById(complaintId);
    if (!complaint) return null;

    const now = new Date();
    const timeFormatted = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const newComment = {
      id: (complaint.comments || []).length + 1,
      user_name: commentObj.user_name,
      user_role: commentObj.user_role,
      comment: commentObj.comment,
      time: timeFormatted
    };

    complaint.comments = complaint.comments || [];
    complaint.comments.push(newComment);
    this.updateComplaint(complaint.id, { comments: complaint.comments });
    return newComment;
  }

  addTimelineStep(complaintId, status, note) {
    const complaint = this.getComplaintById(complaintId);
    if (!complaint) return null;

    const now = new Date();
    const timeFormatted = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                          now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    complaint.timeline = complaint.timeline || [];
    complaint.timeline.push({ status, time: timeFormatted, note });
    this.updateComplaint(complaint.id, { timeline: complaint.timeline });
    return complaint.timeline;
  }

  // --- NOTIFICATIONS CRUD ---
  getNotifications(userId = null) {
    const list = JSON.parse(localStorage.getItem(STORAGE_KEYS.NOTIFICATIONS) || '[]');
    if (userId) {
      return list.filter(n => n.user_id === Number(userId) || n.user_id === 0);
    }
    return list;
  }

  addNotification(notif) {
    const list = JSON.parse(localStorage.getItem(STORAGE_KEYS.NOTIFICATIONS) || '[]');
    const newId = list.length > 0 ? Math.max(...list.map(n => n.id)) + 1 : 1;
    const now = new Date();
    const timeFormatted = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                          now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const newNotif = {
      id: newId,
      user_id: notif.user_id || 1,
      complaint_code: notif.complaint_code || "",
      message: notif.message,
      type: notif.type || "info",
      is_read: false,
      created_at: timeFormatted
    };

    list.unshift(newNotif);
    localStorage.setItem(STORAGE_KEYS.NOTIFICATIONS, JSON.stringify(list));
    return newNotif;
  }

  markNotificationRead(id) {
    const list = JSON.parse(localStorage.getItem(STORAGE_KEYS.NOTIFICATIONS) || '[]');
    const idx = list.findIndex(n => n.id === Number(id));
    if (idx !== -1) {
      list[idx].is_read = true;
      localStorage.setItem(STORAGE_KEYS.NOTIFICATIONS, JSON.stringify(list));
      return true;
    }
    return false;
  }

  markAllNotificationsRead(userId = null) {
    let list = JSON.parse(localStorage.getItem(STORAGE_KEYS.NOTIFICATIONS) || '[]');
    list = list.map(n => {
      if (!userId || n.user_id === Number(userId)) {
        return { ...n, is_read: true };
      }
      return n;
    });
    localStorage.setItem(STORAGE_KEYS.NOTIFICATIONS, JSON.stringify(list));
    return true;
  }

  // --- FEEDBACK CRUD ---
  getFeedback() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.FEEDBACK) || '[]');
  }

  addFeedback(data) {
    const list = this.getFeedback();
    const newId = list.length > 0 ? Math.max(...list.map(f => f.id)) + 1 : 1;
    const now = new Date();
    const timeFormatted = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
                          now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const newFeedback = {
      id: newId,
      complaint_id: Number(data.complaint_id),
      complaint_code: data.complaint_code,
      user_id: Number(data.user_id),
      user_name: data.user_name || "User",
      rating: Number(data.rating || 5),
      comment: data.comment || "",
      created_at: timeFormatted
    };

    list.unshift(newFeedback);
    localStorage.setItem(STORAGE_KEYS.FEEDBACK, JSON.stringify(list));

    // Update complaint status to CLOSED
    this.updateComplaint(data.complaint_id, {
      status: "CLOSED",
      closed_date: timeFormatted
    });

    this.addTimelineStep(data.complaint_id, "CLOSED", `User rated ${newFeedback.rating} stars: "${newFeedback.comment || 'Issue closed'}"`);

    // Notification for technician and admin
    const complaint = this.getComplaintById(data.complaint_id);
    if (complaint && complaint.technician_id) {
      this.addNotification({
        user_id: complaint.technician_id,
        complaint_code: complaint.complaint_code,
        message: `User gave a ${newFeedback.rating}-star review for complaint ${complaint.complaint_code}.`,
        type: "success"
      });
    }

    return newFeedback;
  }

  // --- RESET TO SEED DEMO DATA ---
  resetAllData() {
    localStorage.setItem(STORAGE_KEYS.USERS, JSON.stringify(SEED_USERS));
    localStorage.setItem(STORAGE_KEYS.TECHNICIANS, JSON.stringify(SEED_TECHNICIANS));
    localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(SEED_CATEGORIES));
    localStorage.setItem(STORAGE_KEYS.COMPLAINTS, JSON.stringify(SEED_COMPLAINTS));
    localStorage.setItem(STORAGE_KEYS.NOTIFICATIONS, JSON.stringify(SEED_NOTIFICATIONS));
    localStorage.setItem(STORAGE_KEYS.FEEDBACK, JSON.stringify(SEED_FEEDBACK));
    return true;
  }
}

// Global Storage instance
const StorageManager = new FixMateStorage();
