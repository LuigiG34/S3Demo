# S3Demo

A Symfony PHP project demonstrating integration with AWS S3 for file storage and management, featuring CSV export, image upload, and file download functionalities. Built with Docker, this application showcases secure S3 interactions using IAM policies and Doctrine for PostgreSQL database management.

---

## 1) Requirements

1. **Docker**
2. **Docker Compose**
3. **(Windows) WSL2**
4. **AWS Account** (S3 bucket creation and IAM user setup)

---

## 2) Installation / Run

1. **Clone the Repository**
   ```
   git clone https://github.com/LuigiG34/S3Demo
   cd S3Demo
   ```

2. **Configure AWS S3 Variables**
   - Create a `.env.local` file in the project root.
   - Add the following variables (replace with your AWS credentials and bucket name):
     ```
     AWS_ACCESS_KEY_ID=your-key
     AWS_SECRET_ACCESS_KEY=your-secret
     AWS_REGION=us-east-1
     AWS_BUCKET=your-bucket-name
     DATABASE_URL="postgresql://app:app@db:5432/app?serverVersion=15&charset=utf8"
     ```
   - **Create an S3 User and Policy**:
     - In the AWS IAM Console, create a user (e.g., `s3-symfony-user`).
     - Attach the following policy to the user:
       ```json
       {
           "Version": "2012-10-17",
           "Statement": [
               {
                   "Effect": "Allow",
                   "Action": [
                       "s3:PutObject",
                       "s3:GetObject"
                   ],
                   "Resource": [
                       "arn:aws:s3:::your-bucket-name",
                       "arn:aws:s3:::your-bucket-name/*"
                   ]
               }
           ]
       }
       ```
     - Replace `your-bucket-name` by your own in the JSON above
     - Generate and copy the `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` from the IAM user’s security credentials.

3. **Start Docker Containers**
   ```
   docker compose up -d --build
   ```

4. **Install PHP Dependencies**
   ```
   docker compose exec php composer install
   ```

5. **Generate Database Migration**
   ```
   docker compose run --rm php php bin/console make:migration
   ```

6. **Apply Database Migration**
   ```
   docker compose run --rm php php bin/console doctrine:migrations:migrate
   ```

7. **Load Fixtures**
   ```
   docker compose run --rm php php bin/console doctrine:fixtures:load
   ```

8. **Access the Web Application**
   - Open your browser and navigate to `http://localhost:8000`.
   - Test the following routes:
     - `/export-users`: Exports user data as a CSV to S3.
     - `/upload-image`: Uploads an image to S3.
     - `/download-csv/{filename}`: Downloads a CSV file from S3 (replace `{filename}` with the exported CSV name).

9. **Stop the Application**
   ```
   docker compose down
   ```