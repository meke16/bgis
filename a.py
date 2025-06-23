import random

# Configuration
num_students = 400
grades = [9]*100 + [10]*100 + [11]*100 + [12]*100
sections = ['A', 'B'] * 200  # Alternating A/B
sexes = ['Male', 'Female']
password = 123456

# Function to generate a random phone number
def random_phone():
    return f"555{random.randint(1000000, 9999999)}"

# Generate insert statements
insert_statements = []
for i in range(num_students):
    name = f"Student{str(i+1).zfill(3)}"
    sex = random.choice(sexes)
    grade = grades[i]
    section = sections[i]
    phone = random_phone()
    photo = ''
    username = f"user{str(i).zfill(2)}"
    stmt = f"INSERT INTO students (name, sex, grade, section, phone, photo, username, password) VALUES ('{name}', '{sex}', {grade}, '{section}', '{phone}', '{photo}', '{username}', {password});"
    insert_statements.append(stmt)

# Write to a file or print
with open("insert_students.sql", "w") as f:
    for stmt in insert_statements:
        f.write(stmt + "\n")

print("SQL insert statements saved to insert_students.sql")
