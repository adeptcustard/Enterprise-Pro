CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) CHECK (status IN ('Pending', 'In Progress', 'To Be Reviewed', 'Complete')) NOT NULL DEFAULT 'Pending',
    team VARCHAR(100),
    owner INT REFERENCES users(id) ON DELETE SET NULL,
    assigned_to INT REFERENCES users(id) ON DELETE SET NULL,
    deadline TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    last_updated TIMESTAMP DEFAULT NOW(),
    last_updated_by INT REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE task_actions (
    id SERIAL PRIMARY KEY,
    task_id INT REFERENCES tasks(id) ON DELETE CASCADE,
    action_description TEXT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE task_assignments (
    id SERIAL PRIMARY KEY,
    task_id INT REFERENCES tasks(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE task_comments (
    id SERIAL PRIMARY KEY,
    task_id INT REFERENCES tasks(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE task_files (
    id SERIAL PRIMARY KEY,
    task_id INT REFERENCES tasks(id) ON DELETE CASCADE,
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    uploaded_by INT REFERENCES users(id) ON DELETE SET NULL,
    uploaded_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE vehicles (
    id SERIAL PRIMARY KEY,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    mileage INT DEFAULT 0,
    licence_plate VARCHAR(50) UNIQUE NOT NULL,
    vin VARCHAR(50) UNIQUE NOT NULL,
    comment_log TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE vehicle_todo (
    id SERIAL PRIMARY KEY,
    vehicle_id INT REFERENCES vehicles(id) ON DELETE CASCADE,
    task_description TEXT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE vehicle_files (
    id SERIAL PRIMARY KEY,
    vehicle_id INT REFERENCES vehicles(id) ON DELETE CASCADE,
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    uploaded_by INT REFERENCES users(id) ON DELETE SET NULL,
    uploaded_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE vehicle_bookings (
    id SERIAL PRIMARY KEY,
    vehicle_id INT REFERENCES vehicles(id) ON DELETE CASCADE,
    booked_by INT REFERENCES users(id) ON DELETE SET NULL,
    booking_start TIMESTAMP NOT NULL,
    booking_end TIMESTAMP NOT NULL,
    status VARCHAR(20) CHECK (status IN ('Booked', 'Checked Out', 'Returned', 'Cancelled')) NOT NULL DEFAULT 'Booked',
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE help_requests (
    id SERIAL PRIMARY KEY,
    requester_id INT REFERENCES users(id) ON DELETE CASCADE,
    recipient_id INT REFERENCES users(id) ON DELETE SET NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) CHECK (status IN ('Open', 'Closed')) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE help_responses (
    id SERIAL PRIMARY KEY,
    request_id INT REFERENCES help_requests(id) ON DELETE CASCADE,
    responder_id INT REFERENCES users(id) ON DELETE CASCADE,
    response_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);


